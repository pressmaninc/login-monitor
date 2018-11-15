document.addEventListener('DOMContentLoaded', () => {
    const ablm = document.getElementById('wp-admin-bar-login-monitor');

    setInterval(lm_refresh, LOGIN_MONITOR_CONST.lifetime * 1000);

    function lm_refresh() {
        fetch(
            LOGIN_MONITOR_CONST.url,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: (new URLSearchParams({action: LOGIN_MONITOR_CONST.action})).toString()
            }
        ).then(response => {
            if (ablm) {
                response.json().then(data => {
                    document.getElementById('lm-cnt').innerText = data.length;
                    const ul = document.getElementById('lm-list');
                    ul.innerHTML = '';

                    for (let i in data) {
                        ul.innerHTML += `
                        <li>
                          <span class="lm-badge" style="background-color: #${data[i].color};">
                            <span class="lm-badge-str">
                              ${data[i].display_name.substr(0, 1)}
                            </span>
                          </span>
                          ${data[i].display_name}
                        </li>`;
                    }
                });
            }
        });
    }

    lm_refresh();
});

