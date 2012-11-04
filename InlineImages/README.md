Inline Images
=============

**Purpose**<br />
Read content from an url and converts images into inline images.

**Usage**<br />
$instance = new InlineImages();
echo $instance('http://www.example.com');

**Example**
```html
<img border="0" src="http://www.example.com/test.jpg" width="60" />
```
_BECOMES_
```html
<img border="0"
     src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAKAAAACgCAIAAAAErfB6AAAALHRFWHRDcmVhdGlvbiBUaW1lAFdlZCAxNiBOb3YgMjAxMSAxODozMjoyMCArMDgwMERdE+AAAAAHdElNRQfbCxAKIR1x6twuAAAACXBIWXMAAAsSAAALEgHS3X78AAAABGdBTUEAALGPC/xhBQAAAYVJREFUeNrt0QEJACAQwEC1f+c3hQjjLsFgexZl53cAbxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3DcBZ+ZAj/KfVp2AAAAAElFTkSuQmCC"
     width="60" />
```