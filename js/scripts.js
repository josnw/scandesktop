    function wartemal(schalter) {
        var showme = document.getElementById("wait");
        if (schalter == 'on') {
			showme.style.visibility = "visible";
		} else {
				showme.style.visibility = "hidden";
		}
    }