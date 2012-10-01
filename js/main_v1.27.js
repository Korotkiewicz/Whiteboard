var activeDeactive = function (el, url, elementsToShowHide, additionalHandler) {
    var parameters,
    handler = function (el, response) {
        if (response == '0') {
            if(elementsToShowHide) {
                for(i = 0; i < elementsToShowHide.length; ++i) {
                    document.getElementById(elementsToShowHide[i]).style.display = 'none';
                }
            }
            
            setImageSrc(el, 16, "trafficlight_red.png");
            
            if(additionalHandler) {
                additionalHandler(el, response, url);
            }
        } else if (response == '1') {
            if(elementsToShowHide) {
                for(i = 0; i < elementsToShowHide.length; ++i) {
                    document.getElementById(elementsToShowHide[i]).style.display = 'inline-block';
                }
            }
            
            setImageSrc(el, 16, "trafficlight_green.png");
            
            if(additionalHandler) {
                additionalHandler(el, response, url);
            }
        } else if (response == 'true') {
            setImageSrc(el, 16, "success.png");
            
            if(additionalHandler) {
                additionalHandler(el, response, url);
            }
        } else if (response == 'false') {
            setImageSrc(el, 16, "forbidden.png");
            
            if(additionalHandler) {
                additionalHandler(el, response, url);
            }
        } else {
            document.body.innerHTML = response;
            return false;
        }
    };

    if (el.className.match('red')) {
        parameters = {
            method: 'get'
        };
    } else {
        parameters = {
            method: 'get'
        };
    }
    
    try {
        orbiter.getConnectionMonitor().setAutoReconnectFrequency(-1);
    } catch(e) { }
    
    ajaxRequest(el, url, parameters, handler);
};

var removeElement = function (el, url) {
    var parameters,
    handler = function (el, response) {
        if (response == '0') {
        // el.hide();
        } else if (response == '1' || response == 'true') {
            el.parentNode.parentNode.hide();
        } else if (response == 'false') {
        } else {
            document.body.innerHTML = response;
            return false;
        }
    };

    parameters = {
        method: 'get'
    };
    ajaxRequest(el, url, parameters, handler);
};

var listenToOpenGroups = function (url) {
    var parameters = {
        method: 'get', 
        a: 'getOpenRoom'
    },
    handler = function (el, jsonResponse) {
        var i = 0, gkeysMap = {};
        try {//try to open
            var gkeys = eval('(' + jsonResponse + ')');
            for(i = 0; i < gkeys.length; ++i) {
                var a = document.getElementById('room_' + gkeys[i]), href = url.replace('https', 'http');
                
                a.setAttribute('class', 'classBox openBox');
                a.href = href + '&b=room&window=1&gkey=' + gkeys[i];
                a.title = 'Otwarte - przejdź do zajęć';
                if(!window.location.href.match(/&window=1/)) {
                    a.setAttribute('target', '_blank');
                }
                
                gkeysMap['room_' + gkeys[i]] = true;
            }
            
            setTimeout(function () {
                listenToOpenGroups(url);
            }, 3000);//3s
        } catch(e) {}
        
        //close other
        for(i = 0; i < el.children.length; ++i) {
            if(!gkeysMap[el.children[i].id]) {
                el.children[i].title = 'Zamknięte';
                el.children[i].href = null;
                el.children[i].setAttribute('class', 'classBox');
            }
        }
    };

    ajaxRequest(document.getElementById('rooms'), url, parameters, handler);
};

var setNextGroupDate = function () {
    var time = document.getElementById('time').value,
    shift_week = document.getElementById('shift_week').value,
    frequency = document.getElementById('frequency').value,
    day_of_week = document.getElementById('day_of_week').value,
    nowDate = new Date(), nowDayOfWeek = nowDate.getDay(), now = nowDate.getTime(), oneDayMiliseconds = 86400000,
    weeksFromStart = parseInt((now - startTime) / oneDayMiliseconds / 7),
    nextGroupTime = 0, d, m, nextGroupDatetimeString;
        
    if(now > startTime) {
        if (typeof frequency != 'undefined' && typeof shift_week != 'undefined' && typeof day_of_week != 'undefined') {
            shift_week = parseInt(shift_week);
            
            if(weeksFromStart >= shift_week) {
                weeksFromStart -= shift_week;
            } else {
                nextGroupTime += (shift_week - weeksFromStart) * oneDayMiliseconds * 7;
                weeksFromStart = 0;
            }
            
            weeks = weeksFromStart % frequency;
            
            if(weeks == 0) {
                if(day_of_week - nowDayOfWeek <= 0) {
                    if(day_of_week - nowDayOfWeek < 0 || nowDate.getHours() >= parseInt(time.substring(0,2))) {
                        weeks = 1;
                    }
                }
            } else {
                weeks = frequency - weeks;
            }

            nextGroupTime += now + weeks * oneDayMiliseconds * 7 + (day_of_week - nowDayOfWeek) * oneDayMiliseconds;
            
            d = new Date(nextGroupTime);
            m = d.getMonth() + 1;
            if(m < 10) {
                m = '0' + m;
            }
            nextGroupDatetimeString = d.getDate() + '.' + m + '.' + d.getFullYear();
            if(typeof time != 'undefined') {
                nextGroupDatetimeString = nextGroupDatetimeString + ' o godzinie: ' + time;
            }
            
            document.getElementById('nextGroupDay').innerHTML = 'następne spotkanie odbędzie się: <br/>' + nextGroupDatetimeString;
        }
    }
        
//alert(nowDate.getTime());
};

var changeDate = function (that) {
    var span = that.previousSibling,
    td = that.parentNode,
    input;
    
    if(span.nodeType == 3) {
        span = span.previousSibling;
    }
    
    input = document.createElement('input');
    input.setAttribute('type', 'text');
    input.setAttribute('value', span.innerHTML);
    input.onblur = function () {
        sendDate(that, that.href, input.value);
        span.innerHTML = input.value;
    };
    input.onkeypress = function (evn) {
        if (evn && evn.keyCode == 13) {
            sendDate(that, that.href, input.value);
            span.innerHTML = input.value;
        }
    };
    
    that.style.display = 'none';
    span.innerHTML = '';
    span.appendChild(input);
    
    input.focus();
    
    return false;
};

var sendDate = function (that, url, value) {
    var parameters = {
        method: 'post',
        date: value
    }, handler = function (el, response) {
        var span = el.previousSibling;
    
        if(span.nodeType == 3) {
            span = span.previousSibling;
        }
        
        switch(response) {
            case 'original':
                span.setAttribute('class', '');
                break;
            case '1':
                span.setAttribute('class', 'changedDate');
                break;
            case '0':
                alert('nastąpił problem z zapisaniem');
                break
            default:
                break;
        }
    };
    
    that.style.display = 'inline-block';
    
    ajaxRequest(that, url, parameters, handler);
}

var closeLessonInRoom = function (that, url) {
    if(confirm('Czy napewno chcesz zakończyć zajęcia?')) { 
        activeDeactive(that, url, false, changeOnClickOpenClose); 
    } 
    return false;
}

var openLessonInRoom = function (that, url) {
    if(confirm('Czy napewno chcesz rozpocząć zajęcia?')) { 
        activeDeactive(that, url, false, changeOnClickOpenClose); 
    } 
    return false;
}

var changeOnClickOpenClose = function(el, response, url) {
    var onclick = eval(el.onclick).toString();
    
    setTimeout(function () {
        switch(response) {
            case '0':
                checkIfIsConnected = false;
                
                el.onclick = function () {
                    openLessonInRoom(el, url);
                };
                el.title = 'Rozpocznij zajęcia';
                el.alt = 'Rozpocznij zajęcia';
                
                window.close();
                break;
            case '1':
                checkIfIsConnected = false;
                
                el.onclick = function () {
                    closeLessonInRoom(el, url);
                }
                el.title = 'Zakończ zajęcia';
                el.alt = 'Zakończ zajęcia';
                
                orbiter.getConnectionMonitor().setAutoReconnectFrequency(15000);
                checkIfIsConnected = true;
                orbiter.connect();
                break;
            case 'false':
                //openLessonInRoom(el, url);
                break;
            case 'true':
                break;
            default:
                break;
        }
        
        orbiter.getConnectionMonitor().setAutoReconnectFrequency(15000);
        orbiter.connect();
    }, 1000);
};