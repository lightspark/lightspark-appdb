function openWin(fileToOpen,nameOfWindow,width,height) {
	myWindow = window.open("",nameOfWindow,"menubar=no,scrollbars=yes,status=no,width="+width+",height="+height);
	myWindow.document.open();
	myWindow.document.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">');
	myWindow.document.write('<html><head><title>Screenshot Viewer</title>')
	myWindow.document.write('<style type="text/css">');
	myWindow.document.write('body { margin: 0; padding: 0; background-color: black; }');
	myWindow.document.write('img { border: 0; }');
	myWindow.document.write('p { display: inline; }');
	myWindow.document.write('</style></head><body><p>');
	myWindow.document.write('<a onclick="self.close();" href=""><img src="'+ fileToOpen +'" alt="Screenshot"></a>');
	myWindow.document.write('</p></body></html>');
	myWindow.document.close();
}

function deleteURL(text, url) {
	if (confirm(text)) {
	   self.location = url;
	}
}
