import Notification from "@typo3/backend/notification.js";

(function () {
    let CloudFrontInvalidationModule = {
        init: function () {
            const actionButtons = document.getElementsByClassName('c-awstools__invalidate');

            for (let i = 0, max = actionButtons.length; i < max; i++) {
                let actionButton = actionButtons[i];
                actionButton.addEventListener('click', CloudFrontInvalidationModule.invalidate, false);
            }
        },

        invalidate: function (event) {
            const target = event.target.closest('.c-awstools__invalidate');
            const url = target.href;
            const body = {
                type: target.dataset.type,
                identifier: target.dataset.identifier,
                storage: target.dataset.storage
            };

            target.classList.add('disabled');
            CloudFrontInvalidationModule.fetch(url, body, target);

            event.preventDefault();
        },

        fetch: function (url, body, target) {
            fetch(url, {
                method: 'POST',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            })
                .then(response => CloudFrontInvalidationModule.parseResponse(response))
                .then(result => Notification.success(result.title, result.message))
                .catch(error => Notification.error(error.name, error.message))
                .finally(() => target.classList.remove('disabled'));
        },

        parseResponse: function (response) {
            const data = response.json();
            if (response.status !== 200) {
                throw new Error(data.message);
            }
            return data;
        }
    };

    CloudFrontInvalidationModule.init();

    return CloudFrontInvalidationModule;
}());