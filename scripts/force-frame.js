pageLoc = self.location;
pageAdd = top.location;

if (pageLoc == pageAdd) {
	contentSrc = escape(pageLoc);
	contPage = '../../index.php?' + contentSrc;
	top.location.href = contPage;
}