$(document).ready(function () {
    //setup multiple rows of colours, can also add and remove while spinning but overall this is easier.
    initWheel();
});

function getRemainingSeconds() {
    let now = new Date()
    let minute = now.getMinutes()
    let second = now.getSeconds()
    let rouletteMinute = Number(createdAt[0] + createdAt[1])
    let rouletteSecond = Number(createdAt[3] + createdAt[4])
    console.log(rouletteMinute, rouletteSecond, minute, second)
    let remainingSeconds = null;
    if (minute === rouletteMinute) {
        remainingSeconds = rouletteWaitTime - (second - rouletteSecond)
    } else {
        remainingSeconds = rouletteWaitTime - ((60 - rouletteSecond) + second)
    }

    return remainingSeconds
}

function refreshWaitingTime() {
    setTimeout(
        function () {
            let remainingSeconds = getRemainingSeconds();
            if (remainingSeconds >= 1) {
                document.getElementById('bet-line').style.visibility = 'visible'
                document.getElementById('join-button').style.visibility = 'visible'
                document.getElementById('info-text').innerText = 'Roulette starts in ' + remainingSeconds + ' seconds'
                refreshWaitingTime()
            } else if (remainingSeconds === 0) {
                document.getElementById('bet-line').style.visibility = 'hidden'
                document.getElementById('join-button').style.visibility = 'hidden'
                document.getElementById('info-text').innerText = 'Roulette is rolling...'
                getWinningNumber()
            } else {
                if (winner === null) {
                    document.getElementById('bet-line').style.visibility = 'hidden'
                    document.getElementById('join-button').style.visibility = 'hidden'
                    document.getElementById('info-text').innerText = 'Roulette is rolling...'
                    getWinningNumber()
                } else {
                    document.getElementById('bet-line').style.visibility = 'hidden'
                    document.getElementById('join-button').style.visibility = 'hidden'
                    document.getElementById('info-text').innerText = 'Roulette is rolling...'
                    spinWheel(Number(winner))
                }
            }
        }, 1000);
}

function getHistory() {
    axios.post(
        '/roulette/getLatest'
    ).then(function (response) {
        document.getElementById('last100-1').innerText = response.data.greens
        document.getElementById('last100-2').innerText = response.data.reds
        document.getElementById('last100-3').innerText = response.data.blacks
        for (let i = 1; i <= 5; i++) {
            document.getElementById('history' + String(i)).innerText = response.data.lastResults[i]
            document.getElementById('history' + String(i)).className = '';
            document.getElementById('history' + String(i)).classList.add('card')
            document.getElementById('history' + String(i)).classList.add('square-roulette')
            if (response.data.lastResults[i] === 0) {
                document.getElementById('history' + String(i)).classList.add('green')
            } else if (response.data.lastResults[i] < 8) {
                document.getElementById('history' + String(i)).classList.add('red')
            } else {
                document.getElementById('history' + String(i)).classList.add('black')
            }
        }
    });
}

function validate() {
    let amount = document.getElementById('amount').value
    if (amount !== String(Number(amount))) {
        warnWithTimer('INVALID AMOUNT!', '', 1000)
        return false
    } else if (document.getElementById('selectGreen').classList.contains('selected')) {
        return true
    } else if (document.getElementById('selectRed').classList.contains('selected')) {
        return true
    } else if (document.getElementById('selectBlack').classList.contains('selected')) {
        return true
    } else {
        warnWithTimer('SELECT COLOR!', '', 1000)
        return false
    }
}

document.getElementById('selectGreen').addEventListener("click", ev => {
    document.getElementById('selectGreen').classList.add('selected')
    document.getElementById('selectRed').classList.remove('selected')
    document.getElementById('selectBlack').classList.remove('selected')
})

document.getElementById('selectRed').addEventListener("click", ev => {
    document.getElementById('selectRed').classList.add('selected')
    document.getElementById('selectGreen').classList.remove('selected')
    document.getElementById('selectBlack').classList.remove('selected')
})

document.getElementById('selectBlack').addEventListener("click", ev => {
    document.getElementById('selectBlack').classList.add('selected')
    document.getElementById('selectGreen').classList.remove('selected')
    document.getElementById('selectRed').classList.remove('selected')
})

function joinRoulette() {
    if (validate() === true) {
        axios.post(
            '/roulette/join/' + document.getElementById('amount').value + '/' + document.getElementsByClassName('selected')[0].getAttribute('data-value')
        ).then(function (response) {
            // warnWithTimer('Roulette joined!', '', 1000)
            refreshBalance()
            document.getElementById('amount').value = ''
            document.getElementById('selectGreen').classList.remove('selected')
            document.getElementById('selectRed').classList.remove('selected')
            document.getElementById('selectBlack').classList.remove('selected')

            document.getElementById('my-bets-container').style.visibility = 'visible'
            getUserBets()
        });
    }
}

function getUserBets() {
    axios.post(
        '/roulette/getUserBets'
    ).then(function (response) {
        document.getElementById('my-bets').innerHTML = ''
        document.getElementById('my-bets-text').innerText = 'My current bets: '
        console.log(response.data.side, response.data.value, response.data.size)
        for (let i = 1; i <= response.data.size; i++) {
            if (response.data.side[i] === 'GREEN') {
                document.getElementById('my-bets').innerHTML +=
                    `<div class="square-roulette card green">` + response.data.value[i] + `</div>`
            } else if (response.data.side[i] === 'RED') {
                document.getElementById('my-bets').innerHTML +=
                    `<div class="square-roulette card red">` + response.data.value[i] + `</div>`
            } else {
                document.getElementById('my-bets').innerHTML +=
                    `<div class="square-roulette card black">` + response.data.value[i] + `</div>`
            }
        }
    })
}

function syncPage() {
    getHistory()
    refreshWaitingTime()
}

syncPage()

function getWinningNumber() {
    axios.post(
        '/roulette/start'
    ).then(function (response) {
        spinWheel(Number(response.data.winner))
    });
}

function completeRoulette() {
    setTimeout(
        function () {
            axios.post(
                '/roulette/complete'
            ).then(function (response) {
                getNewRoulette(response.data.createdAt)
                refreshBalance()
            });
        }, 3500);
}

function getNewRoulette(newCreatedAt) {
    createdAt = newCreatedAt
    console.log(createdAt, newCreatedAt)
    syncPage()
    document.getElementById('my-bets-text').innerText = 'My previous bets: '
}

function initWheel() {
    var $wheel = $('.roulette-wrapper .wheel'),
        row = "";

    row += "<div class='row'>";
    row += "  <div class='card red'>1<\/div>";
    row += "  <div class='card black'>14<\/div>";
    row += "  <div class='card red'>2<\/div>";
    row += "  <div class='card black'>13<\/div>";
    row += "  <div class='card red'>3<\/div>";
    row += "  <div class='card black'>12<\/div>";
    row += "  <div class='card red'>4<\/div>";
    row += "  <div class='card green'>0<\/div>";
    row += "  <div class='card black'>11<\/div>";
    row += "  <div class='card red'>5<\/div>";
    row += "  <div class='card black'>10<\/div>";
    row += "  <div class='card red'>6<\/div>";
    row += "  <div class='card black'>9<\/div>";
    row += "  <div class='card red'>7<\/div>";
    row += "  <div class='card black'>8<\/div>";
    row += "<\/div>";

    for (var x = 0; x < 29; x++) {
        $wheel.append(row);
    }
}

function spinWheel(roll) {
    var $wheel = $('.roulette-wrapper .wheel'),
        order = [0, 11, 5, 10, 6, 9, 7, 8, 1, 14, 2, 13, 3, 12, 4],
        position = order.indexOf(roll);

    //determine position where to land
    var rows = 12,
        card = 75 + 3 * 2,
        landingPosition = (rows * 15 * card) + (position * card);

    var randomize = Math.floor(Math.random() * 75) - (75 / 2);

    landingPosition = landingPosition + randomize;

    var object = {
        x: Math.floor(Math.random() * 50) / 100,
        y: Math.floor(Math.random() * 20) / 100
    };

    $wheel.css({
        'transition-timing-function': 'cubic-bezier(0,' + object.x + ',' + object.y + ',1)',
        'transition-duration': '6s',
        'transform': 'translate3d(-' + landingPosition + 'px, 0px, 0px)'
    });

    setTimeout(function () {
        $wheel.css({
            'transition-timing-function': '',
            'transition-duration': '',
        });

        var resetTo = -(position * card + randomize);
        $wheel.css('transform', 'translate3d(' + resetTo + 'px, 0px, 0px)');
        if (roll === 0) {
            document.getElementById('info-text').innerHTML = 'Winner card: ' +
                `<div class="square-roulette card green" style="margin: 0 0 0 15px">` + roll + `</div>`;
        } else if (roll < 8) {
            document.getElementById('info-text').innerHTML = 'Winner card: ' +
                `<div class="square-roulette card red" style="margin: 0 0 0 15px">` + roll + `</div>`;
        } else {
            document.getElementById('info-text').innerHTML = 'Winner card: ' +
                `<div class="square-roulette card black" style="margin: 0 0 0 15px">` + roll + `</div>`;
        }
        completeRoulette()
    }, 6 * 1000);
}