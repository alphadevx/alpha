
// $Id$

var xu = (screen.width)/1024;
var yu = (screen.height)/768;
//detects if the browser is NS6+ or Mozilla 1+
isMoz = (!document.all && document.getElementById) ? true : false;

function makeLayer(id, top, left, width, height, position, visibility, backgroundColor, zIndex) {

	//default attribute settings where none are provided
	position = (position == '') ? 'absolute' : position;
	visibility = (visibility == '') ? 'visible' : visibility;
	backgroundColor = (backgroundColor == '') ? null : backgroundColor;
	zIndex = (zIndex == '') ? '10' : zIndex;
	// centers on the x-axis in the 780x470 iframe!
	if (left == 'centX'){
		var frameHalfWidth = parseInt(390 * xu);
		var centerLayerWidth = parseInt(width * xu);
		var centX = parseInt(frameHalfWidth-(centerLayerWidth/2));
		left = centX;
	}
	// for debugging!
	//alert("makeLayer("+id+","+top+","+left+","+width+","+height+","+position+","+visibility+","+backgroundColor+","+zIndex+")");
	// layer attributes
	this.layer = document.getElementById(id);	
	this.layer.style.position = position;
	
	// to account for Mozilla borders being outside the layer, we will offset the width and height
	if (isMoz && this.layer.type != "button" && this.layer.type != "submit") {		
		var borderW = parseInt(window.getComputedStyle(this.layer, null).borderTopWidth);
		if (!isNaN(borderW)) {
			height = (height-(borderW*2));
			width = (width-(borderW*2));
		}
	}	
	
	this.layer.style.top = parseInt(top * yu);
	this.layer.style.left = parseInt(left * xu);
	this.layer.style.width = parseInt(width * xu);
	this.layer.style.height = parseInt(height * yu);
	this.layer.style.visibility = visibility;
	if (backgroundColor != null)
		this.layer.style.backgroundColor = backgroundColor;
	this.layer.style.zIndex = zIndex;
	// sets up a method for changing the opacity of an existing layer
	this.layer.setOpac = setOpac;
	return this.layer;
	
	function setOpac(newOpac) {
		// method to set the layer's opacity, opacity settings for IE and Mozilla/NS are handled differently
		if (isMoz) {
			this.style.MozOpacity = newOpac/100;
		}else{
			this.filters.alpha.opacity = newOpac;
		}
	}
}
