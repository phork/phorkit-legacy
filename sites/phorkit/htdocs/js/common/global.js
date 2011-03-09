var PHORK = {registry: { cfg: {}}};

Function.prototype.inheritsFrom = function(parentClassOrObject) { 
	if (parentClassOrObject.constructor == Function) {
		this.prototype = new parentClassOrObject();
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject.prototype;
	} else { 
		this.prototype = parentClassOrObject;
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject;
	}
	return this;
};