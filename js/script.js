 function FocusOnInput( FocusId)
 {
	document.getElementById( FocusId).focus();
 }
 
function checkIn ( comboInput, e, typ, orderId, pickId, scanId, packId)   {

    if ( e.keyCode  == 13 ) {     
		item = comboInput.value;
		comboInput.value = '';
       jQuery.ajax ({
		'url' :    'http://172.20.1.248/shipment/itemcheckin.php',
         'data':    'itemId=' + encodeURIComponent(item) + '&typ=' + typ + '&orderId=' + orderId + '&pickId=' + pickId + '&scanId=' + scanId + '&packId=' + packId,
         'success': function(msg) { populateResult(msg); }  
       }); 
    };  
}

function populateResult(msg) {

  console.log( msg );
  result = JSON.parse(msg);	
  var outputdiv    = document.getElementById('OrderItem' + result.itemId + "-" + result.packId);
  var amountdiv    = document.getElementById('OrderItemPackAmount' + result.itemId + "-" +result.packId);

  if(result.status == true)   {
	var classList = outputdiv.className.split(/\s+/);
	outputdiv.classList.remove('packed', 'partpacked');
	outputdiv.classList.add(result.itemPacked);
	
	if(result.itemId == 'Order') {
		var newdiv = document.createElement("div");
		outputdiv.innerHTML = outputdiv.innerHTML + '<h2>Paketdaten werden verarbeitet ...</h2>'
		window.setTimeout('location.href="http://172.20.1.248/shipment/index.php?menu=Versand&showPackOrder=1"',1000);
	} else {
		document.getElementById('singelLabelBtn').style.visibility  = 'visible';	
	}
	
	amountdiv.innerHTML = result.packedAmount;
	

	
	if(result.orderPacked == 'packed') {
		var finisheddiv    = document.getElementById('OrderFinished');
		finisheddiv.style.display = 'block';
		FocusOnInput('finishingOrderBtn');
	}
	
	
	
  } else {
	beep();beep();
	alert(result.info);
  }
  
}

function showDiv(id) {
		var finisheddiv    = document.getElementById(id);
		finisheddiv.style.display = 'block';
}

function deleteDuplicates(name, ab) {
	var elements = document.getElementsByName(name);
	var i;
	var anzahl = elements.length;
	console.log (anzahl + ' ' + name + ' gefunden!');
	for (i = 0; i < (anzahl-ab); i++) {
		console.log(name + ' ' + i + ' wird gelöscht!');
		elements[i].parentNode.removeChild(elements[i]);
	}
}

function singleLabel() {
	deleteDuplicates('packLabel',1);
	deleteDuplicates('addremoveLabel',0);
	document.getElementById('singelLabelBtn').style.visibility  = 'hidden';
	showDiv('OrderFinished');
}

//Daten übernehmen
function copyToInputA( match, listItem)  {

   kname = $(listItem).text()
   $(listItem).parents('.DSResult')
     .hide()
      .siblings('input')
      .val(match);

}

function newPack(container)  {
   var grannydiv = container.parentNode.parentNode.parentNode;
   var parentdiv = container.parentNode.parentNode;
   var newdiv = document.createElement("div");
   grannydiv.insertBefore(newdiv, parentdiv );
   newdiv.className = 'DSEdit smallBorder';
   newdiv.setAttribute("name",'packLabel');
   newdiv.innerHTML = parentdiv.innerHTML;
}

function toField(fromId, toId)  {
   var fromCon = document.getElementById(fromId);
   var toCon = document.getElementById(toId);
   
   toCon.value = fromCon.value.substr(fromCon.value.length-1,1) + toCon.value;
   fromCon.value = fromCon.value.substr(0,fromCon.value.length-1);
}

function delPack(container)  {
   var grannydiv = container.parentNode.parentNode.parentNode;
   var parentdiv = container.parentNode.parentNode;
   if (document.getElementsByName("packLabel").length > 1) {
	grannydiv.removeChild(parentdiv);
   } else {
	 alert("Wenn Du das Paket nicht persönlich abgeben willst, \n  brauchen wir mindestens einen Paketschein! :)")  
   }
}

function beep() {
    var snd = new Audio("data:audio/wav;base64,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qJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=");  
    snd.play();
}


 var currentObj = null;
 var currentObjX = 0;
 var currentObjY = 0;
 
 var startX = 0;
 var startY = 0;
 
 // bool ob aktueller Browser ein IE ist
 var IE = document.all&&!window.opera;
 
 document.onmousemove = doDrag;
 document.onmouseup = stopDrag;
 
  function startDrag(obj) {
 currentObj = obj ;
 startX = currentObjX - currentObj.offsetLeft;
 startY = currentObjY - currentObj.offsetTop;
 }
 

 function doDrag(ereignis) {
 
 currentObjX = (IE) ? window.event.clientX : ereignis.pageX;
 currentObjY = (IE) ? window.event.clientY : ereignis.pageY;
 
 if (currentObj != null) {
 currentObj.style.left = (currentObjX - startX) + "px";
 currentObj.style.top = (currentObjY - startY) + "px";
 }
 }
 
 function stopDrag(ereignis) {
 currentObj = null;
 }
 
 

/*
function settermin(ev,e,typ) {
    var todo = "";
	var kname = "";
	if (typ == 'J') {
		
		kname = JSON.stringify($('f_plantermin').serializeArray());	
		todo = "set";
	
		
	} else if (e.innerHTML.match(/[A-Za-z0-9]/g) == null) {

		todo = "set";
		if (typ == 'X') {
			if(ev.ctrlKey) {
				kname = prompt("Termintext, (für Defaultwert leer lassen)", "");
			} 
		}
	} else {
		
		if(ev.ctrlKey) {
			if (typ == 'X') {
				var dialog = document.getElementById('editTD');
				
			    dialog.innerHTML =  '<span class="right"><button onclick="document.getElementById(\'editTD\').style.display = \'none\';">x</button></span>' +
				
									'<iframe src="http://172.20.1.248/calendar/js/SelTermin.php?id=' + e.id + '&f=kal" width=100% height=100%> </iframe>';
				
				dialog.style.display = 'block';
			} 
			if (typ == 'S') {
				var dialog = document.getElementById('editTD');
				
			    dialog.innerHTML =  '<span class="right"><button onclick="document.getElementById(\'editTD\').style.display = \'none\';">x</button></span>' +
				
									'<iframe src="http://172.20.1.248/calendar/js/SelTermin.php?id=' + e.id + '&f=eve" width=100% height=100%> </iframe>';
				
				dialog.style.display = 'block';
			} 
		} else if(ev.altKey) {
			if (typ == 'X') {
				todo = "set";	
			} 
		} else {
			todo = "unset";	
		}
	}

	if (todo != '') {
		jQuery.ajax ({
		 'url' :    './js/SetTermin.php',
		 'data':      'id=' + encodeURIComponent(e.id) 
					+ '&todo=' + encodeURIComponent(todo)
					+ '&vorlage=' + encodeURIComponent(typ)
					+ '&kname=' + encodeURIComponent(kname),
		 'success': function(msg) { populate_termin(e, msg); }  
		});
	}
}
*/
