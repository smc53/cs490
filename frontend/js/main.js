


function fillTestList(testjson){
	var json = JSON.parse(testjson);

	var list = document.getElementById("testlist");
	for(var i = 0; i < json.tests.length; i++){
		var entry = document.createElement('li');
		entry.appendChild(document.createTextNode(json.tests[i]));
		list.appendChild(entry);
	}
	
}

function loadRequest(){
	var xmlhttp = new XMLHttpRequest();
	        
	//response from middle
	xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4) {
	    	console.log("test ", this.responseText)
	    	//alert(this.responseText)
	        //fillTestList(responseText);
	    }
	};

	xmlhttp.open("POST", "https://web.njit.edu/~smc53/cs490/request-generic.php?request=GetAllTests");
	xmlhttp.send();

}