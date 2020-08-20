define(['TYPO3/CMS/Backend/Notification'], function (Notification) {
    let CloudFrontInvalidationModule = {
        init: function () {
            const actionButtons = document.getElementsByClassName('c-awstools__invalidate');

            for (let i = 0, max = actionButtons.length; i < max; i++) {
                let actionButton = actionButtons[i];
                actionButton.addEventListener('click', CloudFrontInvalidationModule.ajax, false);
            }
        },

        ajax: function (event) {
            const target = event.target.closest('.c-awstools__invalidate');
            const url = target.href;
            const data = {
                type: target.dataset.type,
                identifier: target.dataset.identifier,
                storage: target.dataset.storage
            };

            target.classList.add('disabled');

            fetch(url, {
                method: 'POST',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    const data = response.json();
                    if (response.status !== 200) {
                        console.log(data);
                        throw new Error(data.message);
                    }
                    return data;
                })
                .then(result => Notification.success(result.title, result.message))
                .catch(error => Notification.error(error.name, error.message))
                .finally(() => target.classList.remove('disabled'));

            event.preventDefault();
        },
    };

    CloudFrontInvalidationModule.init();

    return CloudFrontInvalidationModule;
})
