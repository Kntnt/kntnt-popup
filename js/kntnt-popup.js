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

  function getAnimationDuration (animationType, config, element) {
    const durationConfig = config[`${animationType}AnimationDuration`]
    if (durationConfig !== false && typeof durationConfig === 'number' && durationConfig >= 0) {
      return durationConfig
    }
    const cssVarName = `--kntnt-popup-${animationType}-duration-default`
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
    return animationType === 'open' ? 300 : 200
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
    try {
      const event = new CustomEvent(eventName, {
        detail: { popupId: popupId },
        bubbles: true,
        cancelable: true
      })
      document.dispatchEvent(event)
    } catch (e) {
      console.error(`Kntnt Popup: Error dispatching event ${eventName}`, e)
    }
  }

  function showPopup (popupId, config) {
    const popupElement = document.getElementById(popupId)
    if (!popupElement || popupElement.classList.contains('is-open')) {
      return
    }

    if (!canReappear(popupId, config.reappearDelay)) {
      return
    }

    popupElement.dataset.kntntTriggered = 'true'
    clearTriggers(popupId)

    dispatchCustomEvent('kntnt_popup:before_open', popupId)

    MicroModal.show(popupId, {
      onShow: (modal) => {
        if (!modal) return
        const container = modal.querySelector('.kntnt-popup__dialog')
        if (!container) return

        if (config.openAnimation) {
          const duration = getAnimationDuration('open', config, container)
          modal.dataset.openAnimation = config.openAnimation
          container.style.setProperty('--kntnt-popup-open-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-open-animation')
          container.style.removeProperty('--kntnt-popup-open-duration')
        }
        modal.removeAttribute('data-close-animation')
        container.style.removeProperty('--kntnt-popup-close-duration')

        dispatchCustomEvent('kntnt_popup:after_open', popupId)
      },
      onClose: (modal) => {
        if (!modal) return
        storeCloseTimestamp(popupId)

        const container = modal.querySelector('.kntnt-popup__dialog')
        if (!container) {
          dispatchCustomEvent('kntnt_popup:after_close', popupId)
          return
        }

        if (config.closeAnimation) {
          const duration = getAnimationDuration('close', config, container)
          modal.dataset.closeAnimation = config.closeAnimation
          container.style.setProperty('--kntnt-popup-close-duration', `${duration}ms`)
        } else {
          modal.removeAttribute('data-close-animation')
          container.style.removeProperty('--kntnt-popup-close-duration')
        }
        modal.removeAttribute('data-open-animation')
        container.style.removeProperty('--kntnt-popup-open-duration')

        dispatchCustomEvent('kntnt_popup:after_close', popupId)
      },
      openTrigger: 'data-popup-open',
      closeTrigger: 'data-popup-close',
      openClass: 'is-open',
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
      console.warn(`Kntnt Popup: Element with ID #${popupId} not found. Skipping initialization.`)
      return
    }

    if (!canReappear(popupId, config.reappearDelay)) {
      return
    }

    if (config.showAfterTime !== false && config.showAfterTime >= 0) {
      if (config.showAfterTime === 0) {
        setTimeout(() => {
          if (!popupElement.dataset.kntntTriggered && canReappear(popupId, config.reappearDelay)) {
            showPopup(popupId, config)
          }
        }, 0)
      } else {
        activeTimeouts[popupId] = setTimeout(() => {
          if (!popupElement.dataset.kntntTriggered && canReappear(popupId, config.reappearDelay)) {
            showPopup(popupId, config)
          }
        }, config.showAfterTime * 1000)
      }
    }

    if (config.showAfterScroll !== false && config.showAfterScroll >= 0) {
      const scrollHandler = () => {
        if (popupElement.dataset.kntntTriggered || !canReappear(popupId, config.reappearDelay)) {
          if (popupElement.dataset.kntntTriggered && scrollListeners[popupId]) {
            document.removeEventListener('scroll', scrollHandler)
            delete scrollListeners[popupId]
          }
          return
        }

        const scrollHeight = document.documentElement.scrollHeight
        const innerHeight = window.innerHeight
        const currentScroll = window.scrollY
        let scrollPercent = 0
        if (scrollHeight > innerHeight) {
          scrollPercent = (currentScroll / (scrollHeight - innerHeight)) * 100
        } else {
          scrollPercent = (config.showAfterScroll === 0) ? 100 : 0
        }

        if (scrollPercent >= config.showAfterScroll) {
          showPopup(popupId, config)
          document.removeEventListener('scroll', scrollHandler)
          delete scrollListeners[popupId]
        }
      }
      scrollListeners[popupId] = scrollHandler
      document.addEventListener('scroll', scrollHandler, { passive: true })
      scrollHandler()
    }

    if (config.showOnExitIntent) {
      exitIntentPopups.push({ popupId, config })
    }
  })

  if (exitIntentPopups.length > 0 && !isTouchDevice && !exitIntentListenerAttached) {
    let exitIntentTimeout
    const exitIntentHandler = (e) => {
      if (e.clientY <= 0) {
        clearTimeout(exitIntentTimeout)
        exitIntentTimeout = setTimeout(() => {
          const eligiblePopup = exitIntentPopups.find(p => {
            const el = document.getElementById(p.popupId)
            return el && !el.dataset.kntntTriggered && canReappear(p.popupId, p.config.reappearDelay)
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

  document.addEventListener('DOMContentLoaded', () => {
    const triggerElements = document.querySelectorAll('[data-popup-open]')
    triggerElements.forEach(trigger => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault()
        const popupId = trigger.getAttribute('data-popup-open')
        if (popupId) {
          const config = popups.find(p => p.instanceId === popupId)
          if (config) {
            const popupElement = document.getElementById(popupId)
            if (popupElement && !popupElement.classList.contains('is-open') && canReappear(popupId, config.reappearDelay)) {
              showPopup(popupId, config)
            } else if (!canReappear(popupId, config.reappearDelay)) {
              console.warn(`Kntnt Popup [${popupId}]: Click trigger blocked by reappear delay.`)
            }
          } else {
            console.warn(`Kntnt Popup: Config not found for popup ID "${popupId}" triggered by click.`)
          }
        }
      })
    })
  })

})(window, document, window.MicroModal, window.kntntPopupData)