import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.modalElement = this.element.closest(".modal"); // Find, if present, the modal that contains the iframe

        if (this.modalElement) {
            // If the iframe is inside a modal, use the "shown.bs.modal" event
            this.handleModal();
        } else {
            // Otherwise, directly perform resizing
            this.resizeIframeOnChanges();
        }
    }

    handleModal() {
        this.resizeOnModalShown = () => this.resizeIframeOnChanges(); // Refer to the current method

        this.modalElement.addEventListener("shown.bs.modal", this.resizeOnModalShown);

        // Ensure cleanup of the event when the controller is disconnected
        this.element.addEventListener("disconnect", () => {
            this.modalElement.removeEventListener("shown.bs.modal", this.resizeOnModalShown);
        });
    }

    resizeIframeOnChanges() {
        const iframe = this.element; // The associated iframe element

        if (iframe.tagName !== "IFRAME") {
            console.error("This controller is designed to work with iframe elements only.");
            return;
        }

        // Wait for the content to load
        this.waitForIframeContent(iframe, () => {
            this.resizeIframe(iframe);

            // Use MutationObserver to monitor changes in the iframe's content
            const iframeDocument = iframe.contentWindow.document;
            const observer = new MutationObserver(() => this.resizeIframe(iframe));

            observer.observe(iframeDocument.body, {
                childList: true, // Observe direct changes to the DOM structure
                subtree: true, // Also observe changes within nested elements
                attributes: true // Observe changes to attributes
            });

            console.log("MutationObserver started to observe the iframe.");
        });
    }

    resizeIframe(iframe) {
        try {
            const bodyHeight = iframe.contentWindow.document.body.scrollHeight;
            const htmlHeight = iframe.contentWindow.document.documentElement.scrollHeight;
            const contentHeight = Math.max(bodyHeight, htmlHeight);

            iframe.style.height = `${contentHeight + 10}px`;
            console.log(`Iframe height updated: ${contentHeight + 10}px`);
        } catch (error) {
            console.error("Error while resizing the iframe:", error);
        }
    }

    waitForIframeContent(iframe, callback) {
        const interval = setInterval(() => {
            try {
                const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
                if (iframeDocument && iframeDocument.readyState === "complete") {
                    clearInterval(interval);
                    callback();
                }
            } catch (error) {
                console.warn("Error while checking the iframe loading status:", error);
            }
        }, 50); // Check every 50ms if the content is ready
    }
}
