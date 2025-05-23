/**
 * Kntnt Popup
 *
 * This script handles the initialization and behavior of popups created by the Kntnt Popup plugin.
 * It relies on the Micromodal.js library for modal functionality and kntntPopupData (localized from PHP)
 * for popup configurations.
 *
 * @version 1.1.0
 * @param {Window} window - The global window object.
 * @param {Document} document - The global document object.
 * @param {object} MicroModal - The MicroModal library instance.
 * @param {object} kntntPopupData - Localized data object containing popup configurations.
 */
(function (window, document, MicroModal, kntntPopupData) {
  'use strict'

  // Validate essential dependencies and data structures.
  if (
    typeof MicroModal === 'undefined' ||
    typeof kntntPopupData === 'undefined' ||
    !kntntPopupData.popups ||
    !Array.isArray(kntntPopupData.popups)
  ) {
    console.error(
      'Kntnt Popup Critical Error: Essential dependencies (MicroModal or kntntPopupData) are missing or malformed. Halting script execution.',
    )
    return
  }

  // Initialize core configuration and state variables.
  const popupsConfig = kntntPopupData.popups
  const IS_TOUCH_DEVICE = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0)
  const activeTimeouts = {}
  const scrollListeners = {}
  let exitIntentListenerAttached = false
  const exitIntentPopups = []
  const STORAGE_PREFIX = 'kntnt_popup_last_closed_'
  const activeEscKeyListeners = {}

  /**
   * Prevents the ESC key from closing a modal when closeEscKey is false.
   *
   * @param {KeyboardEvent} event - The keyboard event.
   */
  const preventEscapeKey = (event) => {
    if (event.key === 'Escape') {
      event.preventDefault()
      event.stopPropagation()
    }
  }

  /**
   * Retrieves the animation duration for a popup opening or closing.
   * It prioritizes duration set in config, then CSS custom property, then a
   * hardcoded default.
   *
   * @param {string} animationType - The type of animation ('open' or 'close').
   * @param {object} config - The popup's configuration object.
   * @param {HTMLElement} [dialogElement=null] - The dialog DOM element.
   * @returns {number} The animation duration in milliseconds.
   */
  const getAnimationDuration = (animationType, config, dialogElement = null) => {

    // Check for configured duration in popup config.
    const durationConfigKey = `${animationType}AnimationDuration`
    const configuredDuration = config[durationConfigKey]
    if (typeof configuredDuration === 'number' && configuredDuration >= 0) {
      return configuredDuration
    }

    // Attempt to read duration from CSS custom property.
    if (dialogElement) {
      try {
        const cssVarName = `--kntnt-popup-${animationType}-duration-default`
        const durationStr = getComputedStyle(dialogElement).getPropertyValue(cssVarName)
        const durationFromCSS = parseInt(durationStr, 10)
        if (!isNaN(durationFromCSS) && durationFromCSS >= 0) {
          return durationFromCSS
        }
      } catch (error) {
        console.warn(`Kntnt Popup: Could not read CSS variable for ${animationType} duration.`, error)
      }
    }

    // Return hardcoded default duration.
    return animationType === 'open' ? 300 : 200

  }

  /**
   * Checks if a popup can reappear based on its reappearDelay setting and
   * localStorage.
   *
   * @param {string} popupId - The ID of the popup.
   * @param {number} reappearDelaySeconds - The delay in seconds before the
   * popup can reappear.
   * @returns {boolean} True if the popup can reappear, false otherwise.
   */
  const canReappear = (popupId, reappearDelaySeconds) => {

    // Allow immediate reappearance if no delay is configured.
    if (reappearDelaySeconds <= 0) {
      return true
    }

    // Check localStorage for previous close timestamp.
    const storageKey = `${STORAGE_PREFIX}${popupId}`
    const lastClosedTimestamp = localStorage.getItem(storageKey)
    if (!lastClosedTimestamp) {
      return true
    }

    // Calculate if enough time has passed since last closure.
    const now = Date.now()
    const lastClosedTime = parseInt(lastClosedTimestamp, 10)
    return now >= (lastClosedTime + (reappearDelaySeconds * 1000))

  }

  /**
   * Stores the timestamp of when a popup was closed in localStorage.
   *
   * @param {string} popupId - The ID of the popup.
   */
  const storeCloseTimestamp = (popupId) => {

    // Store current timestamp in localStorage for reappear delay calculations.
    const storageKey = `${STORAGE_PREFIX}${popupId}`
    localStorage.setItem(storageKey, Date.now().toString())

  }

  /**
   * Dispatches a custom browser event.
   * Used for `kntnt_popup:before_open`, `kntnt_popup:after_open`, and
   * `kntnt_popup:after_close`.
   *
   * @param {string} eventName - The name of the event to dispatch.
   * @param {string} popupId - The ID of the popup related to the event.
   */
  const dispatchCustomEvent = (eventName, popupId) => {

    // Create and dispatch custom event with popup context.
    try {
      const event = new CustomEvent(eventName, {
        detail: { popupId },
        bubbles: true,
        cancelable: true,
      })
      document.dispatchEvent(event)
    } catch (error) {
      console.error(`Kntnt Popup: Error dispatching event ${eventName}`, error)
    }

  }

  /**
   * Clears any active time or scroll triggers associated with a popup.
   * This prevents a popup from showing if another trigger (e.g., click) has
   * already shown it.
   *
   * @param {string} popupId - The ID of the popup whose triggers should be
   * cleared.
   */
  const clearTriggers = (popupId) => {

    // Clear any active timeout for this popup.
    if (activeTimeouts[popupId]) {
      clearTimeout(activeTimeouts[popupId])
      delete activeTimeouts[popupId]
    }

    // Remove any active scroll listener for this popup.
    if (scrollListeners[popupId]) {
      document.removeEventListener('scroll', scrollListeners[popupId])
      delete scrollListeners[popupId]
    }

  }

  /**
   * Handles the setup and application of open/close animations for a modal.
   *
   * @param {HTMLElement} modalElement - The main modal DOM element.
   * @param {HTMLElement} dialogElement - The dialog part of the modal.
   * @param {object} config - The popup's configuration object.
   * @param {string} animationType - The type of animation ('open' or 'close').
   */
  const setupModalAnimations = (modalElement, dialogElement, config, animationType) => {

    // Configure animation settings based on popup configuration.
    const animationName = config[`${animationType}Animation`]
    const durationCssVar = `--kntnt-popup-${animationType}-duration`
    if (animationName) {
      const duration = getAnimationDuration(animationType, config, dialogElement)
      modalElement.dataset[`${animationType}Animation`] = animationName
      dialogElement.style.setProperty(durationCssVar, `${duration}ms`)
    } else {
      modalElement.removeAttribute(`data-${animationType}-animation`)
      dialogElement.style.removeProperty(durationCssVar)
    }

  }

  /**
   * Displays a popup using Micromodal, handling reappear logic, triggers, and
   * events.
   *
   * @param {string} popupId - The ID of the popup to show.
   * @param {object} config - The configuration object for the popup.
   */
  const showPopup = (popupId, config) => {

    // Validate popup element and check if already open.
    const popupElement = document.getElementById(popupId)
    if (!popupElement || popupElement.classList.contains('is-open')) {
      return
    }

    // Check reappear delay restrictions.
    if (!canReappear(popupId, config.reappearDelay)) {
      return
    }

    // Mark popup as triggered and clear other triggers.
    popupElement.dataset.kntntTriggered = 'true'
    clearTriggers(popupId)

    // Dispatch pre-open event and show modal.
    dispatchCustomEvent('kntnt_popup:before_open', popupId)
    MicroModal.show(popupId, {
      onShow: (modal) => {

        if (!modal) return
        const dialog = modal.querySelector('.kntnt-popup__dialog')
        if (!dialog) return
        setupModalAnimations(modal, dialog, config, 'open')
        modal.removeAttribute('data-close-animation')
        dialog.style.removeProperty('--kntnt-popup-close-duration')

        // Handle ESC key behavior based on configuration
        if (!config.closeEscKey) {
          document.addEventListener('keydown', preventEscapeKey, true)
          activeEscKeyListeners[popupId] = true
        }

        dispatchCustomEvent('kntnt_popup:after_open', popupId)

      },
      onClose: (modal) => {
        if (!modal) return
        storeCloseTimestamp(popupId)
        const dialog = modal.querySelector('.kntnt-popup__dialog')

        // Remove ESC key prevention if it was active
        if (activeEscKeyListeners[popupId]) {
          document.removeEventListener('keydown', preventEscapeKey, true)
          delete activeEscKeyListeners[popupId]
        }

        if (!dialog) {
          dispatchCustomEvent('kntnt_popup:after_close', popupId)
          return
        }
        setupModalAnimations(modal, dialog, config, 'close')
        modal.removeAttribute('data-open-animation')
        dialog.style.removeProperty('--kntnt-popup-open-duration')
        dispatchCustomEvent('kntnt_popup:after_close', popupId)
      },
      openTrigger: 'data-popup-open',
      closeTrigger: 'data-popup-close',
      openClass: 'is-open',
      disableScroll: config.isModal,
      awaitOpenAnimation: !!config.openAnimation,
      awaitCloseAnimation: !!config.closeAnimation,
      disableFocus: !config.isModal,
      debugMode: false,
    })

  }

  /**
   * Initializes a time-delayed trigger for a popup.
   *
   * @param {string} popupId - The ID of the popup.
   * @param {object} config - The configuration object for the popup.
   * @param {HTMLElement} popupElement - The DOM element of the popup.
   */
  const initializeTimeTrigger = (popupId, config, popupElement) => {

    // Check if time trigger is configured.
    if (config.showAfterTime !== false && config.showAfterTime >= 0) {

      // Setup delayed popup display function.
      const delay = config.showAfterTime * 1000
      const timedShow = () => {
        if (!popupElement.dataset.kntntTriggered && canReappear(popupId, config.reappearDelay)) {
          showPopup(popupId, config)
        }
      }

      // Execute immediately or after delay.
      if (delay === 0) {
        setTimeout(timedShow, 0)
      } else {
        activeTimeouts[popupId] = setTimeout(timedShow, delay)
      }

    }

  }

  /**
   * Initializes a scroll-percentage trigger for a popup.
   *
   * @param {string} popupId - The ID of the popup.
   * @param {object} config - The configuration object for the popup.
   * @param {HTMLElement} popupElement - The DOM element of the popup.
   */
  const initializeScrollTrigger = (popupId, config, popupElement) => {

    // Check if scroll trigger is configured.
    if (config.showAfterScroll !== false && config.showAfterScroll >= 0) {

      // Create scroll event handler.
      const scrollHandler = () => {
        if (popupElement.dataset.kntntTriggered || !canReappear(popupId, config.reappearDelay)) {
          if (scrollListeners[popupId]) {
            document.removeEventListener('scroll', scrollHandler)
            delete scrollListeners[popupId]
          }
          return
        }
        const scrollHeight = document.documentElement.scrollHeight
        const { innerHeight } = window
        const { scrollY } = window
        let scrollPercent = 0
        if (scrollHeight > innerHeight) {
          scrollPercent = (scrollY / (scrollHeight - innerHeight)) * 100
        } else if (config.showAfterScroll === 0) {
          scrollPercent = 100
        }
        if (scrollPercent >= config.showAfterScroll) {
          showPopup(popupId, config)
          document.removeEventListener('scroll', scrollHandler)
          delete scrollListeners[popupId]
        }
      }

      // Register scroll listener and perform initial check.
      scrollListeners[popupId] = scrollHandler
      document.addEventListener('scroll', scrollHandler, { passive: true })
      scrollHandler()

    }

  }

  /**
   * Adds a popup to the list for exit-intent checks if configured.
   *
   * @param {string} popupId - The ID of the popup.
   * @param {object} config - The configuration object for the popup.
   */
  const addExitIntentPopup = (popupId, config) => {

    // Add popup to exit-intent list if configured.
    if (config.showOnExitIntent) {
      exitIntentPopups.push({ popupId, config })
    }

  }

  /**
   * Sets up the global exit-intent listener on the document.
   * This listener triggers for any popup configured with `showOnExitIntent`.
   */
  const setupExitIntentListener = () => {

    // Setup exit-intent detection for non-touch devices.
    if (exitIntentPopups.length > 0 && !IS_TOUCH_DEVICE && !exitIntentListenerAttached) {
      let exitIntentTimeout
      const exitIntentHandler = (event) => {
        if (event.clientY <= 0) {
          clearTimeout(exitIntentTimeout)
          exitIntentTimeout = setTimeout(() => {
            const eligiblePopup = exitIntentPopups.find(popupData => {
              const element = document.getElementById(popupData.popupId)
              return element && !element.dataset.kntntTriggered && canReappear(popupData.popupId, popupData.config.reappearDelay)
            })
            if (eligiblePopup) {
              showPopup(eligiblePopup.popupId, eligiblePopup.config)
            }
          }, 100)
        }
      }
      document.documentElement.addEventListener('mouseleave', exitIntentHandler)
      exitIntentListenerAttached = true
    }

  }

  /**
   * Sets up click listeners for manual popup triggers (elements with
   * `data-popup-open` attribute). This is deferred until DOMContentLoaded.
   */
  const setupManualClickTriggers = () => {

    // Setup manual click triggers after DOM is ready.
    document.addEventListener('DOMContentLoaded', () => {
      const triggerElements = document.querySelectorAll('[data-popup-open]')
      triggerElements.forEach(trigger => {
        trigger.addEventListener('click', (event) => {
          event.preventDefault()
          const popupIdToOpen = trigger.getAttribute('data-popup-open')
          if (popupIdToOpen) {
            const targetConfig = popupsConfig.find(popup => popup.instanceId === popupIdToOpen)
            if (targetConfig) {
              const popupElement = document.getElementById(popupIdToOpen)
              if (popupElement && !popupElement.classList.contains('is-open') && canReappear(popupIdToOpen, targetConfig.reappearDelay)) {
                showPopup(popupIdToOpen, targetConfig)
              } else if (popupElement && !canReappear(popupIdToOpen, targetConfig.reappearDelay)) {
                console.warn(`Kntnt Popup [${popupIdToOpen}]: Click trigger blocked by reappear delay.`)
              }
            } else {
              console.warn(`Kntnt Popup: Config not found for popup ID "${popupIdToOpen}" triggered by click.`)
            }
          }
        })
      })
    })

  }

  // Process each popup configuration and initialize triggers.
  popupsConfig.forEach((currentPopupConfig) => {

    // Extract popup ID and configuration.
    const { instanceId: popupId, ...config } = currentPopupConfig
    const popupElement = document.getElementById(popupId)
    if (!popupElement) {
      console.warn(`Kntnt Popup: Element with ID #${popupId} not found. Skipping initialization.`)
      return
    }

    // Skip initialization if reappear delay restrictions apply.
    if (!canReappear(popupId, config.reappearDelay)) {
      return
    }

    // Initialize all configured triggers for this popup.
    initializeTimeTrigger(popupId, config, popupElement)
    initializeScrollTrigger(popupId, config, popupElement)
    addExitIntentPopup(popupId, config)

  })

  // Initialize global listeners and manual triggers.
  setupExitIntentListener()
  setupManualClickTriggers()

})(window, document, window.MicroModal, window.kntntPopupData)