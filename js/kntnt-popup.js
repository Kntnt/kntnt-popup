/**
 * Kntnt Popup Frontend Script
 *
 * Handles popup triggering, animations, and interactions using Micromodal.js
 */
(function (window, document, MicroModal, kntntPopupData) {
    'use strict';

    // Check if MicroModal is loaded
    if (typeof MicroModal === 'undefined') {
        console.error('Kntnt Popup Error: Micromodal library not found.');
        return;
    }

    // Check if popup data is available
    if (typeof kntntPopupData === 'undefined' || !kntntPopupData.popups) {
        // No popups configured for this page
        return;
    }

    const popups = kntntPopupData.popups;
    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    const activeTimeouts = {}; // Store timeouts for clearing
    const scrollListeners = {}; // Store scroll listeners for removal
    let exitIntentListenerAttached = false; // Track if exit intent listener is added

    // --- Helper Functions ---

    /**
     * Get the animation duration based on config or defaults from CSS.
     * @param {string} animationType 'open' or 'close'
     * @param {object} config Popup config object.
     * @param {HTMLElement} element The popup element.
     * @returns {number} Duration in milliseconds.
     */
    function getAnimationDuration(animationType, config, element) {
        const durationConfig = config[`${animationType}AnimationDuration`];
        if (durationConfig !== false && durationConfig >= 0) {
            return durationConfig;
        }
        // If not overridden, try to get from CSS custom property (fallback needed)
        const cssVarName = `--kntnt-popup-${animationType}-duration`;
        const durationStr = getComputedStyle(element).getPropertyValue(cssVarName);
        const duration = parseInt(durationStr, 10);
        return !isNaN(duration) ? duration : (animationType === 'open' ? 300 : 200); // Default fallback
    }

    /**
     * Check if a popup can reappear based on localStorage timestamp and delay.
     * @param {string} popupId The unique ID of the popup.
     * @param {number} delaySeconds The reappear delay in seconds.
     * @returns {boolean} True if the popup can appear, false otherwise.
     */
    function canReappear(popupId, delaySeconds) {
        if (delaySeconds <= 0) {
            return true; // Can always reappear if delay is 0 or less
        }
        const storageKey = `kntnt_popup_last_closed_${popupId}`;
        const lastClosedTimestamp = localStorage.getItem(storageKey);

        if (!lastClosedTimestamp) {
            return true; // Never closed before
        }

        const now = Date.now();
        const lastClosed = parseInt(lastClosedTimestamp, 10);

        // Check if enough time has passed (delay is in seconds, convert to ms)
        return now >= (lastClosed + (delaySeconds * 1000));
    }

    /**
     * Store the closing timestamp in localStorage.
     * @param {string} popupId The unique ID of the popup.
     */
    function storeCloseTimestamp(popupId) {
        const storageKey = `kntnt_popup_last_closed_${popupId}`;
        localStorage.setItem(storageKey, Date.now().toString());
    }

    /**
     * Trigger the custom JavaScript event.
     * @param {string} eventName Name of the event (e.g., 'kntnt_popup:before_open').
     * @param {string} popupId ID of the relevant popup.
     */
    function dispatchCustomEvent(eventName, popupId) {
        const event = new CustomEvent(eventName, {
            detail: { popupId: popupId }
        });
        document.dispatchEvent(event);
    }

    /**
     * Show a specific popup.
     * @param {string} popupId The ID of the popup to show.
     * @param {object} config The configuration object for this popup.
     */
    function showPopup(popupId, config) {
        const popupElement = document.getElementById(popupId);
        if (!popupElement || popupElement.classList.contains('is-open')) {
            return; // Don't show if already open or not found
        }

        // Check reappear delay again right before showing
        if (!canReappear(popupId, config.reappearDelay)) {
            console.log(`Kntnt Popup [${popupId}]: Reappear delay not met.`);
            return;
        }

        // Mark as triggered to prevent other triggers for this session
        popupElement.dataset.kntntTriggered = 'true';
        clearTriggers(popupId); // Remove other potential triggers

        dispatchCustomEvent('kntnt_popup:before_open', popupId);

        MicroModal.show(popupId, {
            // --- Micromodal Config ---
            onShow: (modal) => {
                const container = modal.querySelector('.kntnt-popup-container');
                if (!container) return;

                // Apply animation class and duration
                if (config.openAnimation) {
                    const duration = getAnimationDuration('open', config, container);
                    modal.dataset.openAnimation = config.openAnimation; 
                    container.style.setProperty('--kntnt-popup-open-duration', `${duration}ms`);
                }
            },
            onClose: (modal) => {
                storeCloseTimestamp(popupId); // Store close time on close
                dispatchCustomEvent('kntnt_popup:after_close', popupId);

                const container = modal.querySelector('.kntnt-popup-container');
                if (!container) return;

                // Apply close animation class and duration
                if (config.closeAnimation) {
                    const duration = getAnimationDuration('close', config, container);
                    modal.dataset.closeAnimation = config.closeAnimation;
                    container.style.setProperty('--kntnt-popup-close-duration', `${duration}ms`);
                } else {
                    // No animation, ensure attributes are removed immediately if added by onShow
                    modal.removeAttribute('data-open-animation');
                }
            },
            openTrigger: `data-custom-open-${popupId}`, // Use a unique trigger Micromodal won't find naturally
            closeTrigger: 'data-micromodal-close',
            disableScroll: config.isModal,
            awaitOpenAnimation: !!config.openAnimation,
            awaitCloseAnimation: !!config.closeAnimation,
            disableFocus: !config.isModal, // Trap focus only if modal
            debugMode: false
        });
    }

    /**
     * Remove triggers associated with a popup ID.
     * @param {string} popupId
     */
    function clearTriggers(popupId) {
        if (activeTimeouts[popupId]) {
            clearTimeout(activeTimeouts[popupId]);
            delete activeTimeouts[popupId];
        }
        if (scrollListeners[popupId]) {
            document.removeEventListener('scroll', scrollListeners[popupId]);
            delete scrollListeners[popupId];
        }
    }

    // --- Initialize Each Popup ---
    popups.forEach(config => {
        const popupId = config.instanceId;
        const popupElement = document.getElementById(popupId);

        if (!popupElement) {
            console.warn(`Kntnt Popup: Element with ID #${popupId} not found.`);
            return;
        }

        // Check if popup should appear based on reappear delay
        if (!canReappear(popupId, config.reappearDelay)) {
            return; // Don't attach triggers if delay isn't met
        }

        // --- Setup Triggers ---

        // 1. Time Delay Trigger
        if (config.showAfterTime !== false && config.showAfterTime > 0) {
            activeTimeouts[popupId] = setTimeout(() => {
                if (!popupElement.dataset.kntntTriggered) {
                    showPopup(popupId, config);
                }
            }, config.showAfterTime * 1000); // Convert to milliseconds
        }

        // 2. Scroll Trigger
        if (config.showAfterScroll !== false && config.showAfterScroll >= 0) {
            const scrollHandler = () => {
                if (popupElement.dataset.kntntTriggered) {
                    document.removeEventListener('scroll', scrollHandler); // Already triggered, remove listener
                    return;
                }
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                if (scrollPercent >= config.showAfterScroll) {
                    showPopup(popupId, config);
                    document.removeEventListener('scroll', scrollHandler); // Triggered, remove listener
                    delete scrollListeners[popupId]; // Clean up reference
                }
            };
            scrollListeners[popupId] = scrollHandler; // Store reference for potential removal
            document.addEventListener('scroll', scrollHandler, { passive: true });
        }

        // 3. Exit Intent Trigger (Attach only once globally)
        if (config.showOnExitIntent && !isTouchDevice && !exitIntentListenerAttached) {
            let exitIntentTimeout;
            const exitIntentHandler = (e) => {
                // Basic check: mouse leaving viewport top
                if (e.clientY <= 0) {
                    // Debounce: Clear any previous timeout
                    clearTimeout(exitIntentTimeout);
                    // Set a new timeout to trigger after a short delay
                    exitIntentTimeout = setTimeout(() => {
                        // Find the *first* non-triggered exit-intent popup
                        const eligiblePopup = popups.find(p => {
                            const el = document.getElementById(p.instanceId);
                            return p.showOnExitIntent && el && !el.dataset.kntntTriggered && canReappear(p.instanceId, p.reappearDelay);
                        });

                        if (eligiblePopup) {
                            showPopup(eligiblePopup.instanceId, eligiblePopup);
                        }
                    }, 100); // 100ms delay
                }
            };

            document.documentElement.addEventListener('mouseleave', exitIntentHandler);
            exitIntentListenerAttached = true; // Ensure listener is added only once
        }
    });

})(window, document, window.MicroModal, window.kntntPopupData);