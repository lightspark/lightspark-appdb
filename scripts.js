
function openWin(fileToOpen,nameOfWindow,width,height) {
	myWindow = window.open("",nameOfWindow,"menubar=no,scrollbars=yes,status=no,width="+width+",height="+height);
	myWindow.document.open();
	myWindow.document.write('<HTML><HEAD><TITLE>ScreenShot Viewer</TITLE>')
	myWindow.document.write('<BODY BGCOLOR="#000000" TEXT="#FFFFFF" topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">');
	myWindow.document.write('<a href="javascript:self.close();"><img src="'+ fileToOpen +'" border=0></a>');
	myWindow.document.write('</BODY></HTML>');
	myWindow.document.close();
}

function deleteURL(text, url) {
	if (confirm(text)) {
	   self.location = url;
	}
}


