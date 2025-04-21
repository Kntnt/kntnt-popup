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

  function getAnimationDuration (animationType, config, element) {
    const durationConfig = config[`${animationType}AnimationDuration`]
    if (durationConfig !== false && durationConfig >= 0) {
      return durationConfig
    }
    const cssVarName = `--kntnt-popup-${animationType}-duration`
    const durationStr = getComputedStyle(element).getPropertyValue(cssVarName)
    const duration = parseInt(durationStr, 10)
    return !isNaN(duration) ? duration : (animationType === 'open' ? 300 : 200)
  }

  function canReappear (popupId, delaySeconds) {
    if (delaySeconds <= 0) {
      return true
    }
    const storageKey = `kntnt_popup_last_closed_${popupId}`
    const lastClosedTimestamp = localStorage.getItem(storageKey)

    if (!lastClosedTimestamp) {
      return true
    }

    const now = Date.now()
    const lastClosed = parseInt(lastClosedTimestamp, 10)

    return now >= (lastClosed + (delaySeconds * 1000))
  }

  function storeCloseTimestamp (popupId) {
    const storageKey = `kntnt_popup_last_closed_${popupId}`
    localStorage.setItem(storageKey, Date.now().toString())
  }

  function dispatchCustomEvent (eventName, popupId) {
    const event = new CustomEvent(eventName, {
      detail: { popupId: popupId }
    })
    document.dispatchEvent(event)
  }

  function showPopup (popupId, config) {
    const popupElement = document.getElementById(popupId)
    if (!popupElement || popupElement.classList.contains('is-open')) {
      return
    }

    if (!canReappear(popupId, config.reappearDelay)) {
      console.log(`Kntnt Popup [${popupId}]: Reappear delay not met.`)
      return
    }

    popupElement.dataset.kntntTriggered = 'true'
    clearTriggers(popupId)

    dispatchCustomEvent('kntnt_popup:before_open', popupId)

    MicroModal.show(popupId, {
      onShow: (modal) => {
        const container = modal.querySelector('.kntnt-popup__dialog') // Updated selector based on template
        if (!container) return

        if (config.openAnimation) {
          const duration = getAnimationDuration('open', config, container)
          modal.dataset.openAnimation = config.openAnimation
          container.style.setProperty('--kntnt-popup-open-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-open-animation') // Ensure clean state if no animation
          container.style.removeProperty('--kntnt-popup-open-duration')
        }
      },
      onClose: (modal) => {
        storeCloseTimestamp(popupId)
        dispatchCustomEvent('kntnt_popup:after_close', popupId)

        const container = modal.querySelector('.kntnt-popup__dialog') // Updated selector based on template
        if (!container) return

        if (config.closeAnimation) {
          const duration = getAnimationDuration('close', config, container)
          modal.dataset.closeAnimation = config.closeAnimation
          container.style.setProperty('--kntnt-popup-close-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-close-animation') // Ensure clean state if no animation
          container.style.removeProperty('--kntnt-popup-close-duration')
        }
        // Clean up open animation attributes potentially set by onShow
        modal.removeAttribute('data-open-animation')
        if (container) {
          container.style.removeProperty('--kntnt-popup-open-duration')
        }
      },
      openTrigger: 'data-popup-open', // Changed as requested
      closeTrigger: 'data-popup-close', // Changed as requested [cite: 267]
      openClass: 'is-open', // Default, but explicit
      disableScroll: config.isModal,
      awaitOpenAnimation: !!config.openAnimation,
      awaitCloseAnimation: !!config.closeAnimation,
      disableFocus: !config.isModal,
      debugMode: false
    })
  }

  function clearTriggers (popupId) {
    if (activeTimeouts[popupId]) {
      clearTimeout(activeTimeouts[popupId])
      delete activeTimeouts[popupId]
    }
    if (scrollListeners[popupId]) {
      document.removeEventListener('scroll', scrollListeners[popupId])
      delete scrollListeners[popupId]
    }
  }

  popups.forEach(config => {
    const popupId = config.instanceId
    const popupElement = document.getElementById(popupId)

    if (!popupElement) {
      console.warn(`Kntnt Popup: Element with ID #${popupId} not found.`)
      return
    }

    if (!canReappear(popupId, config.reappearDelay)) {
      return
    }

    if (config.showAfterTime !== false && config.showAfterTime > 0) {
      activeTimeouts[popupId] = setTimeout(() => {
        if (!popupElement.dataset.kntntTriggered) {
          showPopup(popupId, config)
        }
      }, config.showAfterTime * 1000)
    }

    if (config.showAfterScroll !== false && config.showAfterScroll >= 0) {
      const scrollHandler = () => {
        if (popupElement.dataset.kntntTriggered) {
          document.removeEventListener('scroll', scrollHandler)
          return
        }
        const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100
        if (scrollPercent >= config.showAfterScroll) {
          showPopup(popupId, config)
          document.removeEventListener('scroll', scrollHandler)
          delete scrollListeners[popupId]
        }
      }
      scrollListeners[popupId] = scrollHandler
      document.addEventListener('scroll', scrollHandler, { passive: true })
    }

    if (config.showOnExitIntent && !isTouchDevice && !exitIntentListenerAttached) {
      let exitIntentTimeout
      const exitIntentHandler = (e) => {
        if (e.clientY <= 0) {
          clearTimeout(exitIntentTimeout)
          exitIntentTimeout = setTimeout(() => {
            const eligiblePopup = popups.find(p => {
              const el = document.getElementById(p.instanceId)
              return p.showOnExitIntent && el && !el.dataset.kntntTriggered && canReappear(p.instanceId, p.reappearDelay)
            })

            if (eligiblePopup) {
              showPopup(eligiblePopup.instanceId, eligiblePopup)
            }
          }, 100)
        }
      }

      document.documentElement.addEventListener('mouseleave', exitIntentHandler)
      exitIntentListenerAttached = true
    }
  })

})(window, document, window.MicroModal, window.kntntPopupData)