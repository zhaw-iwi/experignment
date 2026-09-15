(function (root, factory) {
    const api = factory();
    if (typeof module === "object" && module.exports) {
        module.exports = api;
    }
    root.StudentChests = api;
}(typeof globalThis !== "undefined" ? globalThis : this, function () {
    "use strict";

    const CHEST_TIMING = Object.freeze({
        charge: 700,
        reveal: 120,
        settle: 360,
        particleTail: 430,
    });

    const CHEST_ASSETS = Object.freeze({
        closed: "assets/chests/chest-closed.png",
        gold: "assets/chests/chest-open-gold.png",
    });

    function normalizeVariant(variant) {
        return variant === "gold" ? "gold" : "gold";
    }

    function imageFor(variant, opened) {
        return opened ? CHEST_ASSETS[normalizeVariant(variant)] : CHEST_ASSETS.closed;
    }

    function pendingCountLabel(count) {
        return Number(count) === 1 ? "1 neue Truhe" : `${Math.max(0, Number(count) || 0)} neue Truhen`;
    }

    function defaultReducedMotion() {
        return typeof window !== "undefined"
            && typeof window.matchMedia === "function"
            && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }

    function preloadChestAssets(ImageConstructor) {
        const Constructor = ImageConstructor || (typeof Image !== "undefined" ? Image : null);
        if (!Constructor) {
            return Promise.resolve();
        }

        return Promise.all(Object.values(CHEST_ASSETS).map((source) => new Promise((resolve, reject) => {
            const image = new Constructor();
            image.onload = async () => {
                try {
                    if (typeof image.decode === "function") {
                        await image.decode();
                    }
                    resolve();
                } catch (error) {
                    reject(error);
                }
            };
            image.onerror = () => reject(new Error(`Chest asset could not be loaded: ${source}`));
            image.src = source;
        })));
    }

    class ChestController {
        constructor(elements, options) {
            this.elements = elements;
            this.acknowledge = options.acknowledge;
            this.prefersReducedMotion = options.prefersReducedMotion || defaultReducedMotion;
            this.onAcknowledged = options.onAcknowledged || (() => {});
            this.onQueueChange = options.onQueueChange || (() => {});
            this.onQueueEmpty = options.onQueueEmpty || (() => {});
            this.queue = [];
            this.current = null;
            this.phase = "idle";
            this.animationToken = 0;
            this.timers = new Map();
            this.acknowledgementStarted = false;
            this.acknowledgementSucceeded = false;
            this.acknowledgementPromise = null;

            this.handleOpen = () => { void this.open(); };
            this.handleContinue = () => { void this.continue(); };
            this.handleRetry = () => { void this.retryAcknowledgement(); };
            this.elements.openButton.addEventListener("click", this.handleOpen);
            this.elements.continueButton.addEventListener("click", this.handleContinue);
            this.elements.retryButton.addEventListener("click", this.handleRetry);
        }

        setEvents(events) {
            this.cancelPresentation();
            this.queue = Array.isArray(events) ? events.slice() : [];
            this.onQueueChange(this.queue.length);
        }

        presentFirst() {
            if (this.queue.length === 0) {
                return false;
            }
            this.render(this.queue[0]);
            return true;
        }

        render(event) {
            this.clearAnimation();
            this.current = event;
            this.phase = "closed";
            this.acknowledgementStarted = false;
            this.acknowledgementSucceeded = false;
            this.acknowledgementPromise = null;

            const variant = normalizeVariant(event.variant);
            this.elements.stage.dataset.variant = variant;
            this.elements.stage.classList.remove("is-charging", "is-bursting", "is-open");
            this.elements.stage.removeAttribute("aria-busy");
            this.elements.openButton.classList.remove("is-charging", "is-bursting", "is-open");
            this.elements.openButton.disabled = false;
            this.elements.openButton.setAttribute("aria-label", "Truhe öffnen");
            this.elements.image.src = imageFor(variant, false);
            this.elements.prompt.textContent = "Öffnen Sie die Truhe, wenn Sie bereit sind.";
            this.elements.position.textContent = `Truhe 1 von ${this.queue.length}`;
            this.elements.reveal.hidden = true;
            this.elements.reveal.classList.remove("is-revealed");
            this.elements.title.textContent = "";
            this.elements.body.textContent = "";
            this.elements.status.hidden = true;
            this.elements.status.textContent = "";
            this.elements.status.className = "chest-save-status";
            this.elements.retryButton.hidden = true;
            this.elements.retryButton.disabled = false;
            this.elements.continueButton.hidden = true;
            this.elements.continueButton.disabled = true;
            this.elements.continueButton.textContent = this.queue.length > 1 ? "Nächste Truhe" : "Weiter";
            this.elements.announcement.textContent = "";
        }

        async open() {
            if (!this.current || this.phase !== "closed") {
                return;
            }

            const event = this.current;
            const token = ++this.animationToken;
            const reduced = this.prefersReducedMotion();

            this.phase = "charging";
            this.elements.openButton.disabled = true;
            this.elements.openButton.setAttribute("aria-label", "Truhe wird geöffnet");

            if (!reduced) {
                this.elements.stage.setAttribute("aria-busy", "true");
                this.elements.stage.classList.add("is-charging");
                this.elements.openButton.classList.add("is-charging");
                this.elements.prompt.textContent = "Die Truhe öffnet sich …";
                if (!await this.delay(CHEST_TIMING.charge, token)) {
                    return;
                }
                this.elements.stage.classList.remove("is-charging");
                this.elements.openButton.classList.remove("is-charging");
                this.elements.stage.classList.add("is-bursting");
                this.elements.openButton.classList.add("is-bursting");
            }

            if (token !== this.animationToken || this.current?.id !== event.id) {
                return;
            }
            this.phase = "bursting";
            this.elements.image.src = imageFor(event.variant, true);

            if (!await this.delay(reduced ? 0 : CHEST_TIMING.reveal, token)) {
                return;
            }
            this.reveal(event);
            void this.acknowledgeOnce(event);

            if (!await this.delay(reduced ? 0 : CHEST_TIMING.settle, token)) {
                return;
            }
            this.settle(reduced, token);
        }

        reveal(event) {
            this.phase = "revealed";
            this.elements.title.textContent = "Teilnahme angerechnet!";
            this.elements.body.textContent = `Ihre Teilnahme an „${event.experimentName}“ wurde angerechnet.`;
            this.elements.reveal.hidden = false;
            this.elements.reveal.classList.add("is-revealed");
            this.elements.prompt.textContent = "Truhe geöffnet.";
            this.elements.continueButton.hidden = false;
            this.elements.announcement.textContent = `Truhe geöffnet: Teilnahme an ${event.experimentName} angerechnet.`;
            this.elements.title.focus({ preventScroll: true });
        }

        settle(reduced, token) {
            if (token !== this.animationToken || !this.current) {
                return;
            }
            this.phase = "open";
            this.elements.openButton.classList.remove("is-charging", "is-bursting");
            this.elements.openButton.classList.add("is-open");
            this.elements.openButton.setAttribute("aria-label", "Truhe geöffnet");
            this.elements.stage.classList.add("is-open");
            this.elements.stage.removeAttribute("aria-busy");
            this.elements.continueButton.disabled = false;

            if (reduced) {
                this.elements.stage.classList.remove("is-charging", "is-bursting");
            } else {
                void this.delay(CHEST_TIMING.particleTail, token).then((current) => {
                    if (current) {
                        this.elements.stage.classList.remove("is-bursting");
                    }
                });
            }
        }

        acknowledgeOnce(event) {
            if (this.acknowledgementStarted) {
                return this.acknowledgementPromise || Promise.resolve(this.acknowledgementSucceeded);
            }
            this.acknowledgementStarted = true;
            return this.sendAcknowledgement(event, this.animationToken);
        }

        sendAcknowledgement(event, presentationToken = this.animationToken) {
            if (this.acknowledgementPromise) {
                return this.acknowledgementPromise;
            }

            this.elements.retryButton.disabled = true;
            this.elements.status.hidden = true;
            this.elements.status.textContent = "";
            const eventId = event.id;
            const request = Promise.resolve()
                .then(() => this.acknowledge(event))
                .then((result) => {
                    if (this.isCurrentPresentation(eventId, presentationToken)) {
                        this.acknowledgementSucceeded = true;
                        this.elements.retryButton.hidden = true;
                        this.elements.status.hidden = true;
                        this.onAcknowledged(result?.event || event);
                    }
                    return true;
                })
                .catch(() => {
                    if (this.isCurrentPresentation(eventId, presentationToken)) {
                        this.acknowledgementSucceeded = false;
                        this.elements.status.textContent = "Die Öffnung konnte noch nicht gespeichert werden.";
                        this.elements.status.className = "chest-save-status text-danger";
                        this.elements.status.hidden = false;
                        this.elements.retryButton.hidden = false;
                        this.elements.retryButton.disabled = false;
                    }
                    return false;
                })
                .finally(() => {
                    if (this.acknowledgementPromise === request) {
                        this.acknowledgementPromise = null;
                    }
                });
            this.acknowledgementPromise = request;
            return request;
        }

        isCurrentPresentation(eventId, presentationToken) {
            return this.current?.id === eventId && this.animationToken === presentationToken;
        }

        async retryAcknowledgement() {
            if (!this.current || this.acknowledgementSucceeded) {
                return;
            }
            this.elements.retryButton.disabled = true;
            const succeeded = await this.sendAcknowledgement(this.current);
            if (succeeded && this.current) {
                this.elements.status.textContent = "Öffnung gespeichert.";
                this.elements.status.className = "chest-save-status text-success";
                this.elements.status.hidden = false;
            }
        }

        async continue() {
            if (!this.current || (this.phase !== "open" && this.phase !== "revealed")) {
                return;
            }

            const eventId = this.current.id;
            if (!this.acknowledgementStarted) {
                void this.acknowledgeOnce(this.current);
            }
            if (this.acknowledgementPromise) {
                this.elements.continueButton.disabled = true;
                this.elements.continueButton.textContent = "Wird gespeichert …";
                await this.acknowledgementPromise;
            }
            if (!this.current || this.current.id !== eventId) {
                return;
            }
            if (!this.acknowledgementSucceeded) {
                this.elements.continueButton.disabled = false;
                this.elements.continueButton.textContent = this.queue.length > 1 ? "Nächste Truhe" : "Weiter";
                this.elements.retryButton.focus({ preventScroll: true });
                return;
            }

            this.queue = this.queue.filter((event) => event.id !== eventId);
            this.onQueueChange(this.queue.length);
            if (this.queue.length === 0) {
                this.cancelPresentation();
                this.onQueueEmpty();
                return;
            }
            this.render(this.queue[0]);
            this.elements.openButton.focus({ preventScroll: true });
        }

        delay(milliseconds, token) {
            if (milliseconds === 0) {
                return Promise.resolve(token === this.animationToken);
            }
            return new Promise((resolve) => {
                const timerId = window.setTimeout(() => {
                    this.timers.delete(timerId);
                    resolve(token === this.animationToken);
                }, milliseconds);
                this.timers.set(timerId, resolve);
            });
        }

        clearAnimation() {
            this.animationToken += 1;
            this.timers.forEach((resolve, timerId) => {
                window.clearTimeout(timerId);
                resolve(false);
            });
            this.timers.clear();
            this.elements.stage.classList.remove("is-charging", "is-bursting", "is-open");
            this.elements.stage.removeAttribute("aria-busy");
            this.elements.openButton.classList.remove("is-charging", "is-bursting", "is-open");
        }

        cancelPresentation() {
            this.clearAnimation();
            this.phase = "idle";
            this.current = null;
            this.acknowledgementStarted = false;
            this.acknowledgementSucceeded = false;
            this.acknowledgementPromise = null;
            this.elements.openButton.disabled = true;
            this.elements.openButton.setAttribute("aria-label", "Truhe öffnen");
            this.elements.image.src = imageFor(null, false);
            this.elements.reveal.hidden = true;
            this.elements.reveal.classList.remove("is-revealed");
            this.elements.status.hidden = true;
            this.elements.retryButton.hidden = true;
            this.elements.continueButton.hidden = true;
            this.elements.announcement.textContent = "";
        }
    }

    return Object.freeze({
        CHEST_TIMING,
        CHEST_ASSETS,
        ChestController,
        imageFor,
        normalizeVariant,
        pendingCountLabel,
        preloadChestAssets,
    });
}));
