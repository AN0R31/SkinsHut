let isBattleOngoing = false

function flip(result, winner, game_id) {
    console.log(result, winner, game_id)
    let coin = document.querySelector(".coin");
    let i = 1;

    coin.style.animation = "none";
    if (result === 'CT') {
        setTimeout(function () {
            coin.style.animation = "spin-heads 5s forwards";
        }, 1000);
    } else {
        setTimeout(function () {
            coin.style.animation = "spin-tails 5s forwards";
        }, 1000);
    }
    document.getElementById('info-area').innerText = 'Flipping...'

    function writeWinner() {
        setTimeout(
            function () {
                document.getElementById('info-area').innerText = 'Winner: ' + winner + '( ' + result + ' SIDE )'
                axios.post(
                    '/coinflip/complete/' + game_id
                ).then(function (response) {});

                function refreshAfterFourSecond() {
                    setTimeout(
                        function () {
                            window.location.reload()
                        }, 2500);
                }

                refreshAfterFourSecond()
            }, 6000);
        refreshBalance()
    }

    writeWinner()
}

function createBattle(userId) {
    if (validate() === true) {
        sendCreateBattleRequest(userId)
    }
}

function validate() {
    let amount = document.getElementById('amount').value
    if (amount !== String(Number(amount))) {
        warnWithTimer('INVALID AMOUNT!', '', 1000)
        return false
    } else if (document.getElementById('ct').checked) {
        return true
    } else if (document.getElementById('t').checked) {
        return true
    } else {
        warnWithTimer('SELECT COIN SIDE!', '', 1000)
        return false
    }
}

function sendCreateBattleRequest(userId) {
    let amount = document.getElementById('amount').value
    let side = document.querySelector('input[name="side"]:checked').value
    axios.post(
        '/coinflip/create/' + userId + '/' + amount + '/' + side
    ).then(function (response) {
        if (response.data.status === false) {
            alert(response.data.error)
        } else {
            // warnWithTimer('Creating battle...', '', 1000)
            document.getElementById('bet-line').style.display = 'none'
            document.getElementById('create-button').style.display = 'none'
            document.getElementById('info-area').style.display = 'block'
            document.getElementById('join-bot').style.display = 'block'
            refreshBalance()
            refreshBattle()
        }
    });
}

function refreshBattle() {
    setTimeout(
        function () {
            console.log(isBattleOngoing)
            if (isBattleOngoing === false) {
                axios.post(
                    '/coinflip/getDetails'
                ).then(function (response) {
                    if (response.data.status === false) {
                        refreshBattle()
                    } else {
                        flip(response.data.result, response.data.winner, response.data.gameId)
                        document.getElementById('join-bot').style.display = 'none'
                        isBattleOngoing = true
                    }
                })
            }
        }, 1000);
}

function addBotAsPlayer2(userId) {
    document.getElementById('join-bot').style.display = 'none'
    axios.post(
        '/coinflip/join/' + userId + '/0'
    ).then(function (response) {
        if (response.data.status === false) {
            alert(response.data.error)
        } else {
            // warnWithTimer('Bot joined your coinflip!', '', 1000)
            flip(response.data.result, response.data.winner, response.data.game_id)
        }
    });
}

document.getElementById('CTlabel').addEventListener("click", event => {
    document.getElementById('Tlabel').style.border = '0px'
    document.getElementById('CTlabel').style.border = '2px solid #b700ff'
})

document.getElementById('Tlabel').addEventListener("click", event => {
    document.getElementById('CTlabel').style.border = '0px'
    document.getElementById('Tlabel').style.border = '2px solid #b700ff'
})