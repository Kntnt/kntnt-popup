(function (window, document, MicroModal, kntntPopupData) {
  'use strict'

  if (typeof MicroModal === 'undefined') {
    console.error('Kntnt Popup Error: Micromodal library not found.')
    return
  }

  if (typeof kntntPopupData === 'undefined' || !kntntPopupData.popups) {
    return
  }

  const popups = kntntPopupData.popups
  const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0)
  const activeTimeouts = {}
  const scrollListeners = {}
  let exitIntentListenerAttached = false
  const exitIntentPopups = []

  /**
   * Gets animation duration from config or CSS variable.
   * @param {string} animationType 'open' or 'close'.
   * @param {object} config Popup configuration object.
   * @param {HTMLElement} element The element to check CSS variable on (usually dialog).
   * @returns {number} Duration in milliseconds.
   */
  function getAnimationDuration (animationType, config, element) {
    const durationConfig = config[`${animationType}AnimationDuration`]
    // Check if config provides a valid non-negative number
    if (durationConfig !== false && typeof durationConfig === 'number' && durationConfig >= 0) {
      return durationConfig
    }
    // Otherwise, try to get from CSS variable
    const cssVarName = `--kntnt-popup-${animationType}-duration-default` // Check default CSS variable
    if (element) {
      try {
        const durationStr = getComputedStyle(element).getPropertyValue(cssVarName)
        const duration = parseInt(durationStr, 10)
        if (!isNaN(duration) && duration >= 0) {
          return duration
        }
      } catch (e) {
        console.warn(`Kntnt Popup: Could not read CSS variable ${cssVarName}`, e)
      }
    }
    // Fallback if CSS variable not found or invalid
    return animationType === 'open' ? 300 : 200
  }

  /**
   * Checks if a popup can reappear based on the last closed time and delay.
   * @param {string} popupId ID of the popup.
   * @param {number} delaySeconds Reappear delay in seconds.
   * @returns {boolean} True if the popup can reappear.
   */
  function canReappear (popupId, delaySeconds) {

    // Always allow if delay is zero or negative
    if (delaySeconds <= 0) {
      return true
    }
    const storageKey = `kntnt_popup_last_closed_${popupId}`
    const lastClosedTimestamp = localStorage.getItem(storageKey)

    // Allow if never closed before
    if (!lastClosedTimestamp) {
      return true
    }

    const now = Date.now()
    const lastClosed = parseInt(lastClosedTimestamp, 10)

    // Check if current time is past the last closed time + delay
    return now >= (lastClosed + (delaySeconds * 1000))

  }

  /**
   * Stores the current timestamp when a popup is closed.
   * @param {string} popupId ID of the popup.
   */
  function storeCloseTimestamp (popupId) {
    const storageKey = `kntnt_popup_last_closed_${popupId}`
    localStorage.setItem(storageKey, Date.now().toString())
  }

  /**
   * Dispatches a custom event.
   * @param {string} eventName Name of the event.
   * @param {string} popupId ID of the related popup.
   */
  function dispatchCustomEvent (eventName, popupId) {
    try {
      const event = new CustomEvent(eventName, {
        detail: { popupId: popupId },
        bubbles: true, // Allow event to bubble up
        cancelable: true
      })
      document.dispatchEvent(event)
    } catch (e) {
      console.error(`Kntnt Popup: Error dispatching event ${eventName}`, e)
    }
  }

  /**
   * Shows the specified popup using MicroModal.
   * @param {string} popupId ID of the popup to show.
   * @param {object} config Configuration object for the popup.
   */
  function showPopup (popupId, config) {
    const popupElement = document.getElementById(popupId)
    // Double check if element exists and is not already open
    if (!popupElement || popupElement.classList.contains('is-open')) {
      return
    }

    // Final check for reappear delay before showing
    if (!canReappear(popupId, config.reappearDelay)) {
      // console.log(`Kntnt Popup [${popupId}]: Reappear delay not met.`); // Optional logging
      return
    }

    popupElement.dataset.kntntTriggered = 'true' // Mark as triggered
    clearTriggers(popupId) // Clear any pending time/scroll triggers for this popup

    dispatchCustomEvent('kntnt_popup:before_open', popupId)

    MicroModal.show(popupId, {
      onShow: (modal) => {
        if (!modal) return
        // console.log(`Kntnt Popup [${popupId}]: Showing modal.`); // Optional logging
        const container = modal.querySelector('.kntnt-popup__dialog')
        if (!container) return

        // Apply open animation settings
        if (config.openAnimation) {
          const duration = getAnimationDuration('open', config, container)
          modal.dataset.openAnimation = config.openAnimation
          // Set CSS variable for specific instance duration override
          container.style.setProperty('--kntnt-popup-open-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-open-animation') // Ensure clean state if no animation
          container.style.removeProperty('--kntnt-popup-open-duration')
        }
        // Clean up close animation attributes potentially left over
        modal.removeAttribute('data-close-animation')
        container.style.removeProperty('--kntnt-popup-close-duration')

        dispatchCustomEvent('kntnt_popup:after_open', popupId) // Dispatch after open event
      },
      onClose: (modal) => {
        if (!modal) return
        // console.log(`Kntnt Popup [${popupId}]: Closing modal.`); // Optional logging
        storeCloseTimestamp(popupId) // Store close time for reappear delay logic

        const container = modal.querySelector('.kntnt-popup__dialog')
        if (!container) {
          dispatchCustomEvent('kntnt_popup:after_close', popupId) // Still dispatch close event
          return
        }

        // Apply close animation settings
        if (config.closeAnimation) {
          const duration = getAnimationDuration('close', config, container)
          modal.dataset.closeAnimation = config.closeAnimation
          // Set CSS variable for specific instance duration override
          container.style.setProperty('--kntnt-popup-close-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-close-animation') // Ensure clean state if no animation
          container.style.removeProperty('--kntnt-popup-close-duration')
        }
        // Clean up open animation attributes potentially set by onShow
        modal.removeAttribute('data-open-animation')
        container.style.removeProperty('--kntnt-popup-open-duration')

        dispatchCustomEvent('kntnt_popup:after_close', popupId) // Dispatch after close event
      },
      openTrigger: 'data-popup-open', // Micromodal attribute for triggers (if any manual ones exist)
      closeTrigger: 'data-popup-close', // Micromodal attribute for close buttons/elements
      openClass: 'is-open', // Class added by Micromodal when open
      disableScroll: config.isModal, // Disable background scroll only if modal
      awaitOpenAnimation: !!config.openAnimation, // Wait for open animation if specified
      awaitCloseAnimation: !!config.closeAnimation, // Wait for close animation if specified
      disableFocus: !config.isModal, // Trap focus only if modal
      debugMode: false // Set to true for console logs from Micromodal
    })
  }

  /**
   * Clears active time and scroll triggers for a specific popup.
   * @param {string} popupId ID of the popup.
   */
  function clearTriggers (popupId) {
    if (activeTimeouts[popupId]) {
      clearTimeout(activeTimeouts[popupId])
      delete activeTimeouts[popupId]
      // console.log(`Kntnt Popup [${popupId}]: Cleared timeout trigger.`); // Optional logging
    }
    if (scrollListeners[popupId]) {
      document.removeEventListener('scroll', scrollListeners[popupId])
      delete scrollListeners[popupId]
      // console.log(`Kntnt Popup [${popupId}]: Cleared scroll trigger.`); // Optional logging
    }
  }

  // --- Initialize Popups ---
  popups.forEach(config => {
    const popupId = config.instanceId
    const popupElement = document.getElementById(popupId)

    if (!popupElement) {
      console.warn(`Kntnt Popup: Element with ID #${popupId} not found. Skipping initialization.`)
      return
    }

    // Initial check: If reappear delay hasn't passed, don't even set up triggers yet.
    // Note: This prevents triggers from firing too early if the page is reloaded within the delay period.
    if (!canReappear(popupId, config.reappearDelay)) {
      // console.log(`Kntnt Popup [${popupId}]: Initial reappear delay not met. Skipping trigger setup.`); // Optional logging
      return
    }

    // --- FIX: Handle showAfterTime = 0 ---
    if (config.showAfterTime !== false && config.showAfterTime >= 0) {
      if (config.showAfterTime === 0) {
        // Show immediately if time is 0 and not already triggered
        // Use setTimeout with 0ms delay to ensure it runs after current execution stack
        setTimeout(() => {
          if (!popupElement.dataset.kntntTriggered && canReappear(popupId, config.reappearDelay)) {
            // console.log(`Kntnt Popup [${popupId}]: Triggering immediately (showAfterTime=0).`); // Optional logging
            showPopup(popupId, config)
          }
        }, 0)
      } else {
        // Set timeout if time is > 0
        activeTimeouts[popupId] = setTimeout(() => {
          // Check again before showing - might have been triggered by scroll/exit intent
          // Also re-check reappear delay in case it was closed and reopened quickly
          if (!popupElement.dataset.kntntTriggered && canReappear(popupId, config.reappearDelay)) {
            // console.log(`Kntnt Popup [${popupId}]: Triggering after ${config.showAfterTime}s delay.`); // Optional logging
            showPopup(popupId, config)
          }
        }, config.showAfterTime * 1000)
        // console.log(`Kntnt Popup [${popupId}]: Timeout trigger set for ${config.showAfterTime}s.`); // Optional logging
      }
    }
    // --- END FIX ---

    // Setup scroll trigger
    if (config.showAfterScroll !== false && config.showAfterScroll >= 0) {
      const scrollHandler = () => {
        // Check if already triggered or if reappear delay is active
        if (popupElement.dataset.kntntTriggered || !canReappear(popupId, config.reappearDelay)) {
          // Cleanup listener if already triggered
          if (popupElement.dataset.kntntTriggered && scrollListeners[popupId]) {
            document.removeEventListener('scroll', scrollHandler)
            delete scrollListeners[popupId]
          }
          return
        }

        // Calculate scroll percentage, avoid division by zero if possible
        const scrollHeight = document.documentElement.scrollHeight
        const innerHeight = window.innerHeight
        const currentScroll = window.scrollY
        let scrollPercent = 0
        if (scrollHeight > innerHeight) {
          scrollPercent = (currentScroll / (scrollHeight - innerHeight)) * 100
        } else {
          // If content doesn't fill viewport, consider 0% scroll sufficient if target is 0
          scrollPercent = (config.showAfterScroll === 0) ? 100 : 0
        }

        if (scrollPercent >= config.showAfterScroll) {
          // console.log(`Kntnt Popup [${popupId}]: Triggering after scroll (${scrollPercent.toFixed(1)}% >= ${config.showAfterScroll}%).`); // Optional logging
          showPopup(popupId, config)
          // Clean up this specific listener immediately after triggering
          document.removeEventListener('scroll', scrollHandler)
          delete scrollListeners[popupId]
        }
      }
      scrollListeners[popupId] = scrollHandler
      document.addEventListener('scroll', scrollHandler, { passive: true })
      // console.log(`Kntnt Popup [${popupId}]: Scroll trigger set for ${config.showAfterScroll}%.`); // Optional logging

      // Initial check in case the user is already past the scroll point on load
      scrollHandler()
    }

    // Add to list if configured for exit intent
    if (config.showOnExitIntent) {
      exitIntentPopups.push({ popupId, config })
    }
  })

  // Setup exit intent listener ONCE if there are any popups configured for it
  if (exitIntentPopups.length > 0 && !isTouchDevice && !exitIntentListenerAttached) {
    let exitIntentTimeout
    const exitIntentHandler = (e) => {
      // Trigger only if mouse goes above the viewport top edge
      if (e.clientY <= 0) {
        clearTimeout(exitIntentTimeout) // Debounce
        exitIntentTimeout = setTimeout(() => {
          // Find the *first* eligible exit-intent popup that hasn't been triggered and can reappear
          const eligiblePopup = exitIntentPopups.find(p => {
            const el = document.getElementById(p.popupId)
            return el && !el.dataset.kntntTriggered && canReappear(p.popupId, p.config.reappearDelay)
          })

          if (eligiblePopup) {
            // console.log(`Kntnt Popup [${eligiblePopup.popupId}]: Triggering on exit intent.`); // Optional logging
            showPopup(eligiblePopup.popupId, eligiblePopup.config)
            // Optionally, remove the listener after first trigger? Or let it potentially trigger another popup later?
            // For now, let it potentially trigger others if the first one's delay expires.
          }
        }, 100) // Small delay to prevent accidental triggers
      }
    }

    document.documentElement.addEventListener('mouseleave', exitIntentHandler)
    exitIntentListenerAttached = true
    // console.log(`Kntnt Popup: Exit intent listener attached.`); // Optional logging
  }

})(window, document, window.MicroModal, window.kntntPopupData)