
// $Id$

/*

Name: insertImage.js
Description: javascript function to make the scalable units for and image, then write in a HTML image tag to call the php file that draws in the scalable image
Author: John Collins, john@design-ireland.net
Revisions:
By:	Date:	Description:

*/

function insertImage(source, width, height, sourceType, quality) {
	/* returns a HTML image tag to the PHP file that draws in a PNG image of the resulting re-scaled image
	Parameters:
	source: the file location of the source images
	width: the width of the outputted image
	height: the height of the images
	sourceType: the type of image that that source is (options are "jpeg","gif", and "png")

	*/
	
	// default quality setting if not provided
	quality = (quality == null) ? 75 : quality;

	// first make the scalable image units (based on the current resolution, compared to a default of 1024x768)
	var xu = screen.width/1024;
	var yu = screen.height/768;

	// now we determine the size of that the image will be scaled to
	var new_width = parseInt(width*xu);
	var new_height = parseInt(height*xu);
	
	document.write('<img src="alpha/util/drawImage.php?source='+source+'&width='+new_width+'&height='+new_height+'&sourceType='+sourceType+'&quality='+quality+'" width="'+new_width+'" height="'+new_height+'" border="0"/>');
}