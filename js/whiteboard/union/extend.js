//This line [13274] fix error in Orbiter_2.0.0.768:
//net.user1.orbiter.Orbiter.window = typeof window == "undefined" ? null : window;

//fix opera problem:
if (/Opera[\/\s](\d+\.\d+)/.test(navigator.userAgent)){
    net.user1.orbiter.System.prototype.hasHTTPDirectConnection = function() {
        return false;
    }
}