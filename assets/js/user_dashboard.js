function copyLatestLink() {
    const text = document.getElementById('latestLink').innerText;

    navigator.clipboard.writeText(text)
        .then(() => {
            alert('Portfolio link copied!');
        })
        .catch(() => {
            alert('Unable to copy link.');
        });
}

function copyPortfolioLink(slug) {
    const url = window.location.origin + '/portfolio-builder/' + slug;

    navigator.clipboard.writeText(url)
        .then(() => {
            alert('Portfolio link copied!');
        })
        .catch(() => {
            alert('Unable to copy link.');
        });
}