function flip(result, winner) {
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
                refreshBalance()

                function refreshAfterFourSecond() {
                    setTimeout(
                        function () {
                            window.location.href = '/home'
                        }, 2500);
                }

                refreshAfterFourSecond()
            }, 6000);
    }

    writeWinner()
}
