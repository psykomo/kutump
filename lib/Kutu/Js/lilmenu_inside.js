/* ================================================================ 
This copyright notice must be untouched at all times.

The original version of this script and the associated (x)html
is available at http://www.stunicholls.com/various/tabbed_pages.html
Copyright (c) 2005-2007 Stu Nicholls. All rights reserved.
This script and the associated (x)html may be modified in any 
way to fit your requirements.
=================================================================== */


onload = function() {
	var g, u = 0;
	while (g = document.getElementById('menu').getElementsByTagName ('DIV') [u++]) {
		if (g.className == 'on' || g.className == 'off') {
		g.onclick = function () {
			var getEls = document.getElementsByTagName('DIV');
				for (var z=0; z<getEls.length; z++) {
				getEls[z].className=getEls[z].className.replace('show', 'hide');
				getEls[z].className=getEls[z].className.replace('on', 'off');
				}
			this.className = 'on';
			var max = this.getAttribute('title');
			document.getElementById(max).className = "show";
			}	
			}
			}
}