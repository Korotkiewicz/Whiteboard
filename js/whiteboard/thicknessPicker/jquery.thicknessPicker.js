var isMouseDown = false;

(function ($) {
    /**
     * Create a couple private variables.
    **/
    var templates = {
        control : $('<div class="thicknessPicker-picker"></div>')
    }, 
    selectedControl = null;

    /**
     * Create our thicknessPicker function
    **/
    $.fn.thicknessPicker = function (options) {
        
        
        return this.each(function () {
            // Setup time. Clone new elements from our templates, set some IDs, make shortcuts, jazzercise.
            var element      = $(this),
            opts         = $.extend({}, $.fn.thicknessPicker.defaults, options),
            lastThickness = (element.val().length > 0) ? element.val() : opts.pickerDefault;
            currentThickness = (element.val().length > 0) ? element.val() : opts.pickerDefault;
            selectedControl = newControl   = templates.control.clone(),
            oldOnmouseUp = document.onmouseup;
        
            document.onmouseup = function (e) {
                if(typeof oldOnmouseUp == 'function') {
                    oldOnmouseUp(e);
                }
            
                if(isMouseDown) {
                    if(currentThickness != lastThickness) {
                        element.val(currentThickness).change();
                    }
                    
                    $.fn.thicknessPicker.changeThickness(currentThickness);
                    
                    isMouseDown = false;
                }
            }

            newControl.bind("mousedown", function (e) {
                isMouseDown = true;
                selectedControl = newControl;
            });

            newControl.bind("mouseup", function (e) {
                var value = $.fn.thicknessPicker.parseValue(e, this);
                isMouseDown = false;
                
                if((value - 1)> currentThickness && e.target.className == 'thicknessPicker-picker' || value < currentThickness) {
                    lastThickness = value;
                    currentThickness = value;
                
                    element.val(value).change();
                
                
                    $.fn.thicknessPicker.changeThickness(value);
                }
            });
            
            newControl.bind("mousemove", function (e) {
                if(isMouseDown) {
                    var value = $.fn.thicknessPicker.parseValue(e, this);
                    if(value> currentThickness && e.target.className == 'thicknessPicker-picker' || (value + 1) < currentThickness) {
                        currentThickness = value;
                
                        $.fn.thicknessPicker.changeThickness(value);
                    }
                }
            });
            
            element.after(newControl);

            element.bind("change", function () {
                $.fn.thicknessPicker.changeThickness();
            });

            element.hide();
        });
    };

    /**
     * Extend thicknessPicker with... all our functionality.
    **/
    $.extend(true, $.fn.thicknessPicker, {

        /**
         * Check whether user clicked on the selector or owner.
        **/
        parseValue : function (event, el) {
            var pos = event.offsetX, 
            maxPos = parseInt($(el).css('border-left-width')), 
            value;
            
            if(typeof pos == 'undefined') {
                pos = event.originalEvent.layerX;
            } else if(pos < 0) {
                pos = maxPos + pos;
            }
            
            value = Math.floor(pos * $.fn.thicknessPicker.defaults.pickerMax / maxPos);
            
            if(!value) {
                value = 0;
            }
            
            value = $.fn.thicknessPicker.defaults.valueMax * value / $.fn.thicknessPicker.defaults.pickerMax;
            
            return value + 1;
        },
        
        parseX : function (value, el) {
            if($.fn.thicknessPicker.defaults.valueMax && value > $.fn.thicknessPicker.defaults.valueMax) {
                value = $.fn.thicknessPicker.defaults.valueMax;
            }
            value = $.fn.thicknessPicker.defaults.pickerMax * value / $.fn.thicknessPicker.defaults.valueMax;
            
            var maxPos = parseInt($(el).css('border-left-width')), 
            x = parseInt((value - 1) * maxPos / $.fn.thicknessPicker.defaults.pickerMax);
                
            return x;
        },
        
        parseY : function (value) {
            return $.fn.thicknessPicker.defaults.pickerMax * value / $.fn.thicknessPicker.defaults.valueMax;;
        },

        /**
         * Update the input with a newly selected thickness.
        **/
        changeThickness : function (value) {
            var valueX = this.parseX(value, selectedControl);
            valueY = this.parseY(value), marginY = 10 - valueY;
            
            selectedControl.html($('<div id="selectedThickness" style="border-left-width: '+ valueX +'px; border-bottom-width: ' + valueY + 'px; margin-top: ' + marginY + 'px"></div>'));
        }


    });

    /**
     * Default thicknessPicker options.
     *
    **/
    $.fn.thicknessPicker.defaults = {
        // thicknessPicker default selected thickenss.
        pickerDefault : 1,

        pickerMin: 1,
        pickerMax: 20,
        valueMax: 30
    };

})(jQuery);
