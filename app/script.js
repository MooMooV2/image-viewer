function init() {
	String.prototype.replaceAll = function(o) {
		var s = this;
		for(var x in o) {
			s = s.replace(new RegExp(x, "gi"), o[x]);
		}
		return s;
	};
	elements.construct({"main":"imgContainer", "img":"fullImg"}); //Define all imprtant HTML elements

	//Create thumbnail loader object
	window.loader = new ThumbLoader(elements.main, JSON.parse(serverSettings));
	loader.request();

	//Add user action event listeners
	window.addEventListener("click", function(event) { input.click(event) }); //Enable mouse
	window.addEventListener("touchstart", function(event) { touch.start(event) }); //Enable touch screen features
	window.addEventListener("keydown", input.key); //Enable keyboard shortcuts
	window.addEventListener("scroll", loader.scroll); //To load more thumbnails
	window.addEventListener("resize", loader.resize);

	//Create full image viewer object
	window.img = new ImgViewer(elements.img, document.getElementById("fullContainer"), document.getElementById("loadingContainer"), elements.main, loader);

	//Remove fallback element
	elements.main.removeChild(document.getElementById("fallback"));
}

//Container for HTML elements
var elements = {
	construct : function(elems) {
		for(var prop in elems) this[prop] = document.getElementById(elems[prop]);
	}
}

//Touchscreen events
var touch = {

	desktop : true, //Is device a desktop device
	midPos : 0, //Half of the screem in pixels
	moveWidth : 0, //Distance touch have to move before img.browse is called
	startPos : 0, //Position where touch starts
	curPos : 0, //Current touch position
	trigPos : 0, //Position where moveWidth was exeeded
	trigger : 0, //Set to 1 or -1 when moveWidth has been exeeded

	start : function(e) {

		//Set a few things if touch event occurs, eg. device is a mobile device
		if(this.desktop) {
			this.desktop = false;
			document.getElementById("buttonContainer").style.display = "none";
			window.addEventListener("touchmove", function(event) { touch.move(event) });
			window.addEventListener("touchend", function(event) { touch.end(event) });
		}

		this.startPos = e.touches[0].clientX;
		this.moveWidth = document.body.offsetWidth * 0.2;
		this.midPos = document.body.offsetWidth / 2;
	},

	move : function(e) {
		this.curPos = e.touches[0].clientX;
		if(this.trigger) {
			elements.img.style.left = this.midPos + this.curPos - this.trigPos + "px";
			elements.img.style.opacity = (this.trigger * (this.trigPos - this.curPos) + this.midPos) / this.midPos;
		}
		else {
			var trigVector = this.curPos - this.startPos;
			if(trigVector > this.moveWidth) {
				this.trigger = 1;
			}
			else if(trigVector < -1 * this.moveWidth) {
				this.trigger = -1;
			}
			this.trigPos = this.curPos;
		}
	},

	end : function(e) {
		if(this.trigger != 0) {
			elements.img.style.display = "none";
			elements.img.style.left = "50%";
			elements.img.style.opacity = "1";
			if(this.trigger == 1) {
				img.browse("back");
			}
			else if(this.trigger == -1) {
				img.browse("next");
			}
			this.trigger = 0;
		}
	}

}

//Input events
var input = {

	click : function(e) {
		if(e.which == 1) {
			if(e.target.id != "copyright") {
				e.preventDefault();
			}
			if(img.opened) {
				switch(e.target.id) {
					case "close":
					case "fullImg":
					case "fullContainer":
						img.close();
						break;
					case "next":
						img.browse("next");
						break;
					case "back":
						img.browse("back");
						break;
				}
			}
			else {
				var target = e.target;
				for(var i = 0; i < 2; i++) {
					if(target.className == "thumb") {
						img.open(target);
						break;
					}
					target = target.parentNode;
				}
			}
		}
	},

	//Keyboard shortcuts
	key : function(e) {
		if(img.opened) {
			switch(e.keyCode) {
				case 27: //ESC
				case 32: //Space
					img.close();
					e.preventDefault();
					break;
				case 39: //Arrow right
				case 38: //Arrow up
					img.browse("next");
					e.preventDefault();
					break;
				case 37: //Arrow left
				case 40: //Arrow down
					img.browse("back");
					e.preventDefault();
					break;
			}
		}
	}
}

//Actions for the image viewer
//Syntax: ImgViewer(<img> element for the full size image, Container where all full image related elements are in, Element for "loading" text, Container for thumbnails)
function ImgViewer(imgElement, wrapper, loading, mainWrapper, loader) {
		var img = new Image(); //A temporary place for the full size image
		var loadComplete = false; //True when the image has compeletly loaded
		var animComplete = false; //True after an animation has stopped
		var self = this;
		this.opened = false; //Contains thumbnail wrapper of false if image is closed

		//Show image when animation is finished
		imgElement.addEventListener("animationend", function(event) {
			this.style.animation = "";
			if(event.animationName.match(/fadeOut.*/)) {
				imgElement.style.display = "none";
				animComplete = true;
				show();
			}
		});

		//Show the image after it has loaded
		img.addEventListener("load", function() {
			loadComplete = true;
			show();
		});

	//Start to load the full image
	function load(thumb) {
		var url = thumb.getAttribute("href");
		img.src = url;
		self.opened = thumb;
	}

	//Show the full image after it has loaded or after animation has played
	function show() {
		if(loadComplete && (animComplete || !touch.desktop)) {
			loadComplete = animComplete = false;
			imgElement.src = img.src;
			imgElement.style.display = "block";
			loading.style.display = "none";
			if(touch.desktop) imgElement.style.animation = "fadeIn 0.15s 1";
		}
	}

	//Open a full image
	this.open = function(thumb) {
		wrapper.style.display = "block";
		loading.style.display = "block";
		imgElement.style.display = "none";
		animComplete = true;
		load(thumb);
		var buttons = document.getElementsByClassName("buttonIcon");
		for(var i in buttons) {
			if(buttons[i].style) {
				buttons[i].style.animation = "buttonIconHover 4s 1";
			}
		}
	}

	//Close a full image
	this.close = function() {
		this.opened = false;
		imgElement.src = "";
		wrapper.style.display = "none";
	}

	//Open previous or next image (load the image and start animation)
	this.browse = function(direction) {
		var t = mainWrapper.children;
		loading.style.display = "block";
		for(var i = 0; i < t.length; i++) {
			if(t[i] == this.opened) {
				if(direction == "next") {
					if(!loader.end || i < (t.length - 1)) {
						loader.next(i+1,self.next);
						if(touch.desktop) imgElement.style.animation = "fadeOutLeft 0.3s 1";
					}
				}
				else {
					if(i > 0) {
						if(touch.desktop) imgElement.style.animation = "fadeOutRight 0.3s 1";
						load(t[i-1]);
					}
				}
				break;
			}
		}
	}

	this.next = function(index) {
		load(mainWrapper.children[index]);
	}

}

//Constructor for thumbnail loader object
//Syntax: ThumbLoader(Server script URL, Container element for thumbnails, Object for settings coming from the server)
function ThumbLoader(mainWrapper, settings) {
	var self = this;
	var fileIndex = 0; //Count of images the server has sent + other files in the same directory on the server
	var all = []; //All loaded thumbnails
	var buffer = []; //Thumbnails which are not yet in a row
	var loading = 0; //How many thumbnails are loading or -1 when no loading operation is active
	this.end = false; //No more images to load on the server
	var imgPath = settings.imagePath;
	var thumbPath = settings.thumbPath;
	var rowHeight = settings.rowHeight;
	var loadOffset = settings.loadOffset;
	var reqQty = settings.batchSize;
	var thumbHMargin = 0; //Horizontal margin between thumbnails
	var wWidth = window.innerWidth; //To launch resize event only when width changes
	var imgCallback = null; //Callback function to view next image in ImgViewer
	var imgNextIndex = 0; //Next image to show in ImgViewer

	//Find margin widths from style sheet and set thumb height
	Array.prototype.some.call(document.styleSheets,	function (sheet) {
		return Array.prototype.some.call(sheet.cssRules, function (rule) {
			if(rule.selectorText === ".thumb") {
				var ml = rule.style.marginLeft ? Number(rule.style.marginLeft.replace("px","")) : 0;
				var mr = rule.style.marginRight ? Number(rule.style.marginRight.replace("px","")) : 0;
				thumbHMargin = ml + mr;
				rule.style.height = rowHeight + "px";
				return true;
			}
			return false;
		});
	});

	//Define AJAX object
	var ajax = new XMLHttpRequest();

	//Handle AJAX response
	ajax.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				try {
					var response = JSON.parse(this.responseText);
					var thumbs = response.thumbs;
					fileIndex = response.index;
					loading = thumbs.length;
					for(var i = 0; i < thumbs.length; i++) {
						var thumb = new ThumbObj(thumbs[i]);
						buffer.push(thumb);
						all.push(thumb);
					}
				}
				catch(err) {
					console.log(err);
				}
			}
			else if(this.status == 204) {
				self.end = true;
				loading = -1;
			}
		}
	}

	//Get more thumbnails from the server
	function request() {
		if(!self.end) {
			loading = 0;
			ajax.open("GET", "./index.php?getImgs=" + reqQty + "&index=" + fileIndex, true);
			ajax.send();
		}
	}

	//Make request public
	this.request = request;

	//Constructor for a new thumbnail object
	function ThumbObj(imgName) {
		var self = this;
		this.width = 0;

		//Define HTML elements
		var thumbLoc = thumbPath + imgName.replaceAll({"\"":"-22-", "%":"-25-", "&":"-26-", "'":"-27-", "\.(jpe?g|png|gif)$":".jpg"});
		var wrapper = document.createElement("A");
		var titleElem = document.createElement("SPAN");
		var titleText = document.createTextNode(imgName.replaceAll({"\.[a-zA-Z]{3,4}$":"", "_":" "}));
		wrapper.className = "thumb";
		wrapper.style.backgroundImage = "url(" + thumbLoc + ")";
		wrapper.href = imgPath + imgName;
		titleElem.className = "thumbTitle";
		titleElem.appendChild(titleText);
		wrapper.appendChild(titleElem);
		this.wrapper = wrapper;

		//Load the thumbnail and check it's width after it has loaded
		var thumb = new Image();
		var callback = function() {
			self.width = thumb.width;
			self.wrapper.style.maxWidth = self.width + "px";
			loading--;
			thumb.removeEventListener("load", callback);
			if(loading == 0) {
				drawRow(buffer);
			}
		}
		thumb.addEventListener("load", callback);
		thumb.src = thumbLoc;
	}

	//Appends thumbnails to the page after they are loaded
	function drawRow(thumbs) {
		var rowWidth = 0;
		var row = [];
		for(var i = 0; i < thumbs.length; i++) {
			rowWidth += thumbs[i].width;
			row.push(thumbs[i]);
			thumbs[i].wrapper.style.flexBasis = thumbs[i].width + "px";
			mainWrapper.appendChild(thumbs[i].wrapper);
			if(rowWidth >= mainWrapper.offsetWidth) {
				for(var u = 0; u < row.length; u++) {
					row[u].wrapper.style.flexBasis = "calc(" + (100 * (row[u].width / rowWidth)) + "% - " + thumbHMargin + "px)";
				}
				thumbs = buffer = thumbs.slice(row.length,thumbs.length);
				i -= row.length;
				row = [];
				rowWidth = 0;
			}
		}
		if(imgCallback !== null) {
			imgCallback(imgNextIndex);
			imgCallback = null;
		}
		if(document.body.scrollHeight < (Number(document.documentElement.scrollTop || window.pageYOffset) + window.innerHeight + loadOffset)) {
			request();
		}
		else {
			loading = -1;
		}
	}

	//Load more thumbnails when page is scrolled
	this.scroll = function() {
		if(loading == -1) {
			if(document.body.scrollHeight < (Number(document.documentElement.scrollTop || window.pageYOffset) + window.innerHeight + loadOffset)) {
				request();
			}
		}
	}

	//Load new images when images are browsed in full view mode
	this.next = function(index,callback) {
		if(index < all.length) {
			callback(index);
		}
		else {
			imgCallback = callback;
			imgNextIndex = index;
			request();
		}
	}

	//Rerender all thumbnails when page is resized
	this.resize = function() {
		if(loading == -1 && this.wWidth != window.innerWidth) {
			this.wWidth = window.innerWidth;
			drawRow(all);
		}
	}

}
