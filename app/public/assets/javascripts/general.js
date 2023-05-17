function redirectToRoute(twigRoute) {
    window.location.href = twigRoute
}

function getLastMatches() {
    axios.post(
        '/game/getLatest'
    ).then(function (response) {
        if (response.data.status === false) {
            alert(response.data.error)
        } else {
            for (let i = 1; i <= 5; i++) {
                if (response.data.eta[i] === 1) {
                    document.getElementById(String(i)).classList.remove('green')
                    document.getElementById(String(i)).classList.remove('red')
                    document.getElementById(String(i)).classList.remove('purple')
                    document.getElementById(String(i)).classList.add('purple')
                    document.getElementById(String(i)).innerHTML =
                        `<a href="/coinflip/join/`+response.data.gameIds[i]+`">Join ` + response.data.players1[i] + ` for this ` + response.data.values[i] +` COIN ` + response.data.types[i] +`!</a>`
                } else if (response.data.eta[i] === 2) {
                    document.getElementById(String(i)).classList.remove('green')
                    document.getElementById(String(i)).classList.remove('red')
                    document.getElementById(String(i)).classList.remove('purple')
                    document.getElementById(String(i)).classList.add('red')
                    document.getElementById(String(i)).innerHTML =
                        response.data.players1[i] + ` is flipping against ` + response.data.players2[i]+ ` in a ` + response.data.values[i] +` COIN ` + response.data.types[i] +`!`
                } else {
                    if (response.data.eta[i] === 3) {
                        document.getElementById(String(i)).classList.remove('green')
                        document.getElementById(String(i)).classList.remove('red')
                        document.getElementById(String(i)).classList.remove('purple')
                        document.getElementById(String(i)).classList.add('green')
                        if (response.data.winners[i] === 1) {
                            document.getElementById(String(i)).innerHTML =
                                response.data.players1[i] + ` won ` + response.data.values[i] +` COINS at ` + response.data.types[i] + ` against ` + response.data.players2[i] + `!`
                        } else {
                            document.getElementById(String(i)).innerHTML =
                                response.data.players2[i] + ` won ` + response.data.values[i] +` COINS at ` + response.data.types[i] + ` against ` + response.data.players1[i] + `!`
                        }
                    }
                }
            }
        }
    });
    refreshLastMatches()
}

function refreshLastMatches() {
    setTimeout(
        function () {
            getLastMatches()
        }, 1000);
}

function refreshBalance() {
    let userId = Number(document.getElementById('balance').getAttribute('data-id'))

    axios.post(
        '/user/getBalance/' + userId
    ).then(function (response) {
        if (response.data.status === false) {
            alert(response.data.error)
        } else {
            document.getElementById('balanceRaw').innerText = response.data.balance
        }
    });
}

function warnWithTimer(title, warning, duration) {
    let timerInterval
    Swal.fire({
        title: "<h5 style='color:white'>" + title + "</h5>",
        html: warning,
        timer: duration,
        background: 'rgba(0, 0, 0, 0.75)',
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading()
            const b = Swal.getHtmlContainer().querySelector('b')
            timerInterval = setInterval(() => {
                b.textContent = Swal.getTimerLeft()
            }, 100)
        },
        willClose: () => {
            clearInterval(timerInterval)
        }
    }).then((result) => {
        /* Read more about handling dismissals below */
        if (result.dismiss === Swal.DismissReason.timer) {
            console.log('I was closed by the timer')
        }
    })
}