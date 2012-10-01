////==============================================================================
// ORBITER VARIABLES
//==============================================================================
// The Orbiter object, which is the root of Union's JavaScript client framework
var orbiter;
var isLessonOpen = false;
// The MessageManager object, for sending and receiving messages
var msgManager;
// If true than pan with debug log info will be shown
var showDebug = false;//true;
var retriveHistory = true;
// A convenience reference to net.user1.orbiter.UPC, which provides a
// list of valid client/server UPC messages. See: http://unionplatform.com/specs/upc/
var UPC = net.user1.orbiter.UPC;
// The ID of the room users will join in order to draw together
// A hash of client attribute names used in this application. Each client sets a
// "thickness" attribute and a "color" attribute, specify the thickness and 
// color of the current line being drawn.
var Attributes = {
    THICKNESS:"thickness", 
    COLOR:"color",
    TOOL:"tool"
};

var Tools = {
    PENCIL: 'pencil',
    ERASER: 'eraser',
    TEXT:   'text'
}

if(typeof pl == "undefined") {
    pl = {
        edu: {
            libratus: {
                orbiter: {}
            }
        }
    };
} else if(typeof pl.edu == "undefined") {
    pl.edu = {
        libratus: {}
    };
} else if(typeof pl.edu.libratus == "undefined") {
    pl.edu.libratus = {};
}

pl.edu.libratus.orbiter = {
    credential: {
        login: 'Ryszard',
        password: 'secret_password'
    },
    room: {
        id: "pl.edu.libratus.room.dojo"
    },
    server: {
        IP: "tryunion.com",
        PORT: 80
    },
    libraryURL: './',
    whiteboard: {
        canvas: {
            color: '#FCFCFC',
            width: 600, //px
            height: 404 //px
        },
        line: {
            color: '#000000'//'#AAAAAA'
        },
        font: 'Arial',
        tool: {
            cursor: {//class names
                pencil: 'cursor_pencil',
                eraser: 'cursor_eraser',
                text:   'cursor_text'
            }
        }
    }
};

//connectionStatus contain status of connection as integer (0 = not connected, 1 = connected, 2 = logged,...)
var connectionStatus = 0;
var checkIfIsConnected = true;
var delayInUserIDEnquire = 1000; //1s

// A hash of room message names used in this application. MOVE means move the
// drawing pen to the specified position. PATH supplies a list of points to be
// drawn.
var Messages = {
    MOVE:"MOVE", 
    PATH:"PATH",
    TEXT:'TEXT',
    UNDO:'UNDO',
    CLEAR:'CLEAR',
    CHAT:"CHAT_MESSAGE",
    SYSTEM:"SYSTEM_MESSAGE",
    HISTORY:"HISTORY"
};

//==============================================================================
// LOCAL USER VARIABLES
//==============================================================================
// A flag to track whether the user is drawing or not
var isPenDown = false;
var randomAccountNumber;


// Line defaults
var defaultTool = 'pencil';
var defaultLineColor = '#000000';//"#AAAAAA";
var defaultLineThickness = 1;
var defaultEraserThickness = 30;
var defaultFontSize = 12;
var maxLineThickness = 30;

// Tracks the current location of the user's drawing pen
var localPen = {};

// The user's line styles 
var localLineColor = pl.edu.libratus.orbiter.whiteboard.line.color;
var localLineThickness = defaultLineThickness;
var localEraserThickness = defaultEraserThickness;
var localTextSize = defaultFontSize;

var localTool = defaultTool;

// A list of points in a path to send to other connected users
var bufferedPath = [];
// A timestamp indicating the last time a point was added to the bufferedPath
var lastBufferTime = new Date().getTime();

//==============================================================================
// REMOTE USER VARIABLES
//==============================================================================
// A hash of pen positions for remote users, in the following 
// format ("2345" is an example client ID):
//  {"2345": {x:10, y:10}}
var userCurrentPositions = {};
// A hash of pending drawing commands sent by remote users, the following format: 
//  {"2345": [{commandName:moveTo, arg:{x:10, y:10}}, {commandName:lineTo, arg:{x:55, y:35}}]};
var userCommands = [];
// A hash of line colors for remote users, in the following format:
//  {"2345": "#CCCCCC"};
var userColors = {};
// A hash of line thicknesses for remote users, in the following format:
//  {"2345": 5};
var userThicknesses = {};
// A hash of tools for remote users, in the following format:
//  {"2345": 'pencil'};
var userTools = {};

//==============================================================================
// DRAWING VARIABLES
//==============================================================================
// The HTML5 drawing canvas
var canvas;
var canvasLocker;
var canvasWrapper;
var occupantlist;
// This array will store the restoration points of the canvas
var restorePoints = [];
// The drawing canvas's context, through which drawing commands are performed
var context;
var textInput;
// A hash of drawing commands executed by UnionDraw's rendering process
var DrawingCommands = {
    LINE_TO:       "lineTo",
    MOVE_TO:       "moveTo",
    TEXT:          'text',
    UNDO:          'undo',
    CLEAR:          'clear',
    SET_THICKNESS: "setThickness",
    SET_COLOR:     "setColor",
    SET_TOOL:      "setTool",
    END_HISTORY:    "endHistory"
};

//==============================================================================
// TIMER VARIABLES
//==============================================================================
// The ID for a timer that sends the user's drawing path on a regular interval
var broadcastPathIntervalID;
// The ID for a timer that executes drawing commands sent by remote users
var processDrawingCommandsIntervalID;

//==============================================================================
// TOUCH-DEVICE VARIABLES
//==============================================================================
var hasTouch = false;

//==============================================================================
// INITIALIZATION
//==============================================================================
// Trigger init() when the document finishes loading
//window.onload = init;

// Main initialization function
/**
 * @param string url Local url to js with whiteboard
 * @param string divId ID of div where should be build whiteboard
 * @param string l User login
 * @param string p User password
 */
function init (url, divId, l, p, roomId, serverIP, serverPort, whiteboardBGColor, whiteboardLineColor) {
    if(!url || !divId) {
        alert('init error');
        return false;
    }
    pl.edu.libratus.orbiter.libraryURL = url;
    
    if(l) {
        pl.edu.libratus.orbiter.credential.login = l;
    }
    if(p) {
        pl.edu.libratus.orbiter.credential.password = p;
    }
    if(roomId) {
        pl.edu.libratus.orbiter.room.id = roomId;
    }
    if(serverIP) {
        pl.edu.libratus.orbiter.server.IP = serverIP;
    }
    if(serverPort) {
        pl.edu.libratus.orbiter.server.PORT = serverPort;
    }
    if(whiteboardBGColor) {
        pl.edu.libratus.orbiter.whiteboard.canvas.color = whiteboardBGColor;
    }
    if(whiteboardLineColor) {
        pl.edu.libratus.orbiter.whiteboard.line.color = whiteboardLineColor;
        localLineColor = defaultLineColor = whiteboardLineColor;
    }
    
    //start building DOM:
    var wrapper = document.getElementById(divId), whiteboardWrapperDiv, chatWrapperDiv, userListWrapperDiv, tmpDiv1, tmpDiv2, tmpDiv3;
    if(!wrapper) {
        alert('Element: "' + divId + '" not exists');
        return;
    }
    
    tmpDiv1 = document.createElement('div');
    tmpDiv1.id = 'shadow';
    tmpDiv1.setAttribute('title', 'Zajęcia są wyłączone');
    tmpDiv1.style.backgroundImage = 'url(' + pl.edu.libratus.orbiter.libraryURL + '/shadow.png)';
    tmpDiv1.style.width = '2000px';
    tmpDiv1.style.height = '2000px';
    tmpDiv1.style.position = 'absolute';
    tmpDiv1.style.top = '0px';
    tmpDiv1.style.left = '0px';
    tmpDiv1.style.overflow = 'hidden';
    document.body.appendChild(tmpDiv1);
    
    tmpDiv1 = document.createElement('div');
    tmpDiv1.id = 'unionWrapper';
    
    wrapper.appendChild(tmpDiv1);
    whiteboardWrapperDiv = document.createElement('div');
    chatWrapperDiv = document.createElement('div');
    userListWrapperDiv = document.createElement('div');
    whiteboardWrapperDiv.id = 'whiteboardWrapper';
    chatWrapperDiv.id = 'chatWrapper';
    userListWrapperDiv.id = 'userListWrapper';
    tmpDiv1.appendChild(whiteboardWrapperDiv);
    tmpDiv1.appendChild(chatWrapperDiv);
    tmpDiv1.appendChild(userListWrapperDiv);
    
    tmpDiv2 = document.createElement('div');
    tmpDiv2.setAttribute('class', 'clear');
    tmpDiv1.appendChild(tmpDiv2);
    if(showDebug) {
        //Contains the debugging log messages:
        tmpDiv2 = document.createElement('div');
        tmpDiv2.id = 'logPane';
        tmpDiv2.style.display = 'none';
        tmpDiv2.setAttribute('style', 'float: left;');
        tmpDiv1.appendChild(tmpDiv2);
    }
    
    {//chat
        //Drop down menus for selecting line thickness and color
        tmpDiv1 = document.createElement('div');
        tmpDiv1.id = 'chatBorder';
        chatWrapperDiv.appendChild(tmpDiv1);
        {
            tmpDiv2 = document.createElement('div');
            tmpDiv2.id = 'chatToolbar';
            tmpDiv2.setAttribute('class', 'unionToolbar');
            tmpDiv2.innerHTML = '<div id="chatInfo">Czat</div>'
            tmpDiv1.appendChild(tmpDiv2);
            
            tmpDiv2 = document.createElement('div');
            tmpDiv2.id = 'chatPane';
            tmpDiv1.appendChild(tmpDiv2);
        }
            
        //The outgoing chat form
        tmpDiv1 = document.createElement('div');
        tmpDiv1.id = 'chatForm';
        chatWrapperDiv.appendChild(tmpDiv1);
        {
            tmpDiv2 = document.createElement('input');
            tmpDiv2.id = 'outgoing';
            tmpDiv2.type = 'text';
            tmpDiv2.setAttribute('onkeydown', 'if (event.keyCode == 13) sendMessage()');
            tmpDiv1.appendChild(tmpDiv2);
                
            tmpDiv2 = document.createElement('input');
            tmpDiv2.id = 'chatSubmit';
            tmpDiv2.type = 'submit';
            tmpDiv2.value = 'Wyślij';
            tmpDiv2.setAttribute('onclick', 'sendMessage()');
            tmpDiv1.appendChild(tmpDiv2);
        }
    }
    {//whiteboard
        tmpDiv1 = document.createElement('div');
        tmpDiv1.id = 'toolbar';
        tmpDiv1.setAttribute('class', 'unionToolbar');
        whiteboardWrapperDiv.appendChild(tmpDiv1);
        
        {//toolbar - tmpDiv1 - Drop down menus for selecting line thickness and color
            tmpDiv2 = document.createElement('div');
            tmpDiv2.id = 'controls';
            tmpDiv1.appendChild(tmpDiv2);
            
            tmpDiv2.innerHTML = '<div style="display: block; float: left;">Rozmiar: <input type="text" id="thickness" name="thickness" value="1">Kolor: <input type="text" id="color" name="color" value="' + pl.edu.libratus.orbiter.whiteboard.line.color + '"/><select id="tool" style="display: none;"><option name="pencil" selected value="pencil">Ołówek</option><option name="eraser" value="eraser">Gumka</option><option name="text" value="text">Gumka</option></select></div>';
            tmpDiv2.innerHTML += '<a id="pencil" class="selectedTool" onclick="selectTool(\'pencil\');" title="Ołówek"><img src="' + pl.edu.libratus.orbiter.libraryURL + '/pencil.png" width="18" height="18"/></a>';
            tmpDiv2.innerHTML += '<a id="eraser" class="tool" onclick="selectTool(\'eraser\');" title="Gumka"><img src="' + pl.edu.libratus.orbiter.libraryURL + '/eraser.png" width="18" height="18"/></a>';
            tmpDiv2.innerHTML += '<a id="text" class="tool" onclick="selectTool(\'text\');" title="Tekst"><img src="' + pl.edu.libratus.orbiter.libraryURL + '/text.png" width="18" height="18"/></a>';
            tmpDiv2.innerHTML += '<a id="clear" class="tool" onmousedown="clearWhiteboard();" title="Wyczyść"><img src="' + pl.edu.libratus.orbiter.libraryURL + '/clear.png" width="18" height="18"/></a>';
            tmpDiv2.innerHTML += '<a id="undo" class="tool" onmousedown="undo();" title="Cofnij"><img src="' + pl.edu.libratus.orbiter.libraryURL + '/undo.png" width="18" height="18"/></a>';
                    
            tmpDiv2 = document.createElement('div');
            tmpDiv2.id = 'status';
            tmpDiv1.appendChild(tmpDiv2);
        }
        
        canvas = document.createElement('canvas');
        canvas.id = 'canvas';
        canvas.setAttribute('class', 'cursor_pencil');//set cursor to pencil -> default tool
        
        whiteboardWrapperDiv.appendChild(canvas);
    }
    {//userList
        tmpDiv1 = document.createElement('div') 
        tmpDiv1.id = 'userListToolbar';
        tmpDiv1.setAttribute('class', 'unionToolbar');
        tmpDiv1.innerHTML = '<div id="chatInfo">Zalogowani użytkownicy</div>';
        userListWrapperDiv.appendChild(tmpDiv1);
       
        //Contains the room occupants:
        tmpDiv1 = document.createElement('select');
        tmpDiv1.id = 'occupantlist';
        tmpDiv1.setAttribute('multiple', 'multiple');
        userListWrapperDiv.appendChild(tmpDiv1);
    }
    //end DOM
    
    initCanvas();
    jQuery('#color').colorPicker();
    jQuery('#thickness').thicknessPicker();
    
    registerInputListeners();
    initOrbiter();
    iPhoneToTop();
  
    setStatus("Trwa łączenie do interaktywnej tablicy...");
    displayChatMessage('Trwa łączenie do chat-u...', 'system');
    
    setTimeout(checkIsConnected, 5000);
}

function checkIsConnected () {
    if(connectionStatus < 1 && checkIfIsConnected) {
        alert('Nastąpił problem z połączeniem');
    }
}

function getComputedWidth(theElt){
    if(typeof G_vmlCanvasManager != "undefined"){
        tmphght = document.getElementById(theElt).offsetWidth;
    }
    else{
        docObj = document.getElementById(theElt);
        var tmphght1 = document.defaultView.getComputedStyle(docObj, "").getPropertyValue("width");
        tmphght = tmphght1.split('px');
        tmphght = tmphght[0];
    }
    
    
    if (navigator.userAgent.indexOf("Firefox")!=-1) {
        return tmphght; 
    } else {
        return tmphght - 10; 
    }
}

// Set up the drawing canvas
function initCanvas () {
    // Retrieve canvas reference
    canvas = document.getElementById("canvas");
    canvasWrapper = canvas.parentNode;
  
    // If IE8, do IE-specific canvas initialization (required by excanvas.js)
    if (typeof G_vmlCanvasManager != "undefined") {
        this.canvas = G_vmlCanvasManager.initElement(this.canvas);
    }
  
    // Size canvas
    canvas.width  = pl.edu.libratus.orbiter.whiteboard.canvas.width;//getComputedWidth('whiteboardWrapper');
    canvas.height = pl.edu.libratus.orbiter.whiteboard.canvas.height;
    canvas.style.width = canvas.width + 'px';
    canvas.style.height = canvas.height + 'px';
    canvas.style.backgroundColor = pl.edu.libratus.orbiter.whiteboard.canvas.color;
    
    var whiteboard = document.getElementById('whiteboardWrapper');
    whiteboard.width = canvas.width;
    whiteboard.style.width = canvas.style.width;
    
    textInput = document.createElement('input');
    textInput.setAttribute('onkeypress', 'return inputKeypressListener(event)');
    textInput.setAttribute('type', 'text');
    textInput.setAttribute('class', 'canvasText');
    textInput.style.fontFamily = pl.edu.libratus.orbiter.whiteboard.font;
    textInput.style.fontSize = localTextSize + 'px';
    textInput.style.color = localLineColor;
  
    // Retrieve context reference, used to execute canvas drawing commands
    context = canvas.getContext('2d');
    context.lineCap = "round";
  
// Set control panel defaults
//document.getElementById("thickness").selectedIndex = 0;
// document.getElementById("color").selectedIndex = 1;
}

// Register callback functions to handle user input
function registerInputListeners () {
    var oldOnmouseUp = document.onmouseup;
    
    canvas.onmousedown = pointerDownListener;
    document.onmousemove = pointerMoveListener;
    document.onmouseup = function (e) {
        if(typeof oldOnmouseUp == 'function') {
            oldOnmouseUp();
        }
        
        pointerUpListener(e);
    }
    document.ontouchstart = touchDownListener;
    document.ontouchmove = touchMoveListener;
    document.ontouchend = touchUpListener;
    document.getElementById("thickness").onchange = thicknessSelectListener;
    document.getElementById("color").onchange = colorSelectListener;
    document.getElementById("tool").onchange = toolSelectListener;
    
    var oldOnkeydown = document.onkeydown;
    document.onkeydown = function (e) {
        if(typeof oldOnkeydown == 'function') {
            oldOnkeydown(e);
        }
        
        keyDownListener(e);
    }
}

// Triggered when the log is updated
function logUpdateListener (e) {
    displayLogMessage(e.getTimeStamp() + " " + e.getLevel() + ": " + e.getMessage());
}

function isLessonOpen() {
    return isLessonOpen;
}

function openLesson() {
    isLessonOpen = true;
    
    document.getElementById('shadow').style.display = 'none';
}

function closeLesson() {
    isLessonOpen = false;

    document.getElementById('shadow').style.display = 'block';
    
    try {
        orbiter.dispose();
    } catch(e) {
        
    }
}

// Displays a single log message
function displayLogMessage (message) {
    // Make the new log message element
    var msg = document.createElement("span");
    msg.appendChild(document.createTextNode(message));
    msg.appendChild(document.createElement("br"));
 
    // Append the new message to the chat
    var logPane = document.getElementById("logPane");
    logPane.style.display = 'block';
    logPane.appendChild(msg);
 
    // Trim the log to 500 messages
    if (logPane.childNodes.length > 500) {
        logPane.removeChild(logPane.firstChild);
    }
    logPane.scrollTop = logPane.scrollHeight;
}


// Outputs the current log history
function displayLogHistory (e) {
    var history = orbiter.getLog().getHistory();
    for (var i = 0; i < history.length; i++) {
        displayLogMessage(history[i]);
    }
}


// Initialize Orbiter, which handles multiuser communications
function initOrbiter () {
    // Create the Orbiter instance, used to connect to and communicate with Union
    orbiter = new net.user1.orbiter.Orbiter();
    orbiter.getConnectionMonitor().setAutoReconnectFrequency(15000);

    if(showDebug) {
        // Set up the debugging log
        displayLogHistory();
        orbiter.getLog().setLevel(net.user1.logger.Logger.DEBUG);
        orbiter.enableConsole();
        orbiter.getLog().addEventListener(net.user1.logger.LogEvent.UPDATE, logUpdateListener, this);
    }


    // If required JavaScript capabilities are missing, abort
    if (!orbiter.getSystem().isJavaScriptCompatible()) {
        setStatus("Twoja przeglądarka nie jest wspierana przez to narzędzie.")
        return;
    }
  
  
    // Register for Orbiter's connection events
    orbiter.addEventListener(net.user1.orbiter.OrbiterEvent.READY, readyListener, this);
    orbiter.addEventListener(net.user1.orbiter.OrbiterEvent.CLOSE, closeListener, this);
    //    orbiter.addEventListener(net.user1.orbiter.OrbiterEvent.PROTOCOL_INCOMPATIBLE, connectionErrorListener, this);
    //    orbiter.addEventListener(net.user1.orbiter.OrbiterEvent.CONNECT_REFUSED, connectionErrorListener, this);
    //    orbiter.getConnectionManager().addEventListener(net.user1.orbiter.ConnectionEvent.DISCONNECT, connectionErrorListener, this);


    // Retrieve a reference to the MessageManager, used for sending messages to
    // and receiving messages from Union Server
    msgManager = orbiter.getMessageManager();
    
    // The occupant listbox
    occupantlist = document.getElementById("occupantlist");
  
    // Connect to Union Server
    try {
        //if (navigator.userAgent.indexOf("Firefox")!=-1) {
        orbiter.getMessageManager().removeListenersOnDisconnect = false;
        //}
        orbiter.connect(pl.edu.libratus.orbiter.server.IP, pl.edu.libratus.orbiter.server.PORT);
    } catch(e) {
        alert('Nastąpił problem z nawiązaniem połączenia z serwerem ' + pl.edu.libratus.orbiter.server.IP);
        if(showDebug) {
            alert(e);
        }
    }
}

//==============================================================================
// ORBITER EVENT LISTENERS
//==============================================================================
// Triggered when the connection to Union Server is ready
function readyListener (e) {
    connectionStatus = 1;
    displayChatMessage('Połączono.', 'status', 2);
    // Register for UPC messages from Union Server
    //msgManager.addMessageListener(UPC.JOINED_ROOM, joinedRoomListener, this);

    
    //randomAccountNumber = Math.floor(Math.random() * 1000);
    orbiter.getAccountManager().createAccount(pl.edu.libratus.orbiter.credential.login, pl.edu.libratus.orbiter.credential.password)
    orbiter.getAccountManager().addEventListener(
        net.user1.orbiter.AccountManagerEvent.CREATE_ACCOUNT_RESULT,
        createAccountResultListener, this);
    orbiter.getAccountManager().addEventListener(net.user1.orbiter.AccountEvent.LOGIN_RESULT,
        loginResultListener, this);
    //    
    msgManager.addMessageListener(UPC.ROOM_OCCUPANTCOUNT_UPDATE, 
        roomOccupantCountUpdateListener, this);  
    msgManager.addMessageListener(UPC.ROOM_SNAPSHOT, roomSnapshotListener, this);
    msgManager.addMessageListener(UPC.CLIENT_ATTR_UPDATE, clientAttributeUpdateListener, this);
    msgManager.addMessageListener(UPC.CLIENT_ADDED_TO_ROOM, clientAddedToRoomListener, this);
    msgManager.addMessageListener(UPC.CLIENT_REMOVED_FROM_ROOM, clientRemovedFromRoomListener, this);
    
  
    // Register for custom messages from other users
    msgManager.addMessageListener(Messages.MOVE, moveMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.PATH, pathMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.TEXT, textMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.UNDO, undoMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.CLEAR, clearMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.CHAT, chatMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.SYSTEM, systemMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    msgManager.addMessageListener(Messages.HISTORY, historyMessageListener, this, [pl.edu.libratus.orbiter.room.id]);
    
    //    // Create a room for the drawing and chat app, then join it
    //    msgManager.sendUPC(UPC.CREATE_ROOM, roomID);
    //    msgManager.sendUPC(UPC.JOIN_ROOM, roomID);
    
    modules = new net.user1.orbiter.RoomModules();
    modules.addModule('pl.edu.libratus.module.room.history.HistoryRoomModule', net.user1.orbiter.ModuleType.CLASS);
    //modules.addModule('pl.edu.libratus.module.room.stayalive.StayAliveRoomModule', net.user1.orbiter.ModuleType.CLASS);
    //    
    var settings = new net.user1.orbiter.RoomSettings();
    settings.removeOnEmpty = false;

    chatRoom = orbiter.getRoomManager().createRoom(pl.edu.libratus.orbiter.room.id, settings, null, modules);
    chatRoom.addEventListener(net.user1.orbiter.RoomEvent.ADD_OCCUPANT, addOccupantListener);
    chatRoom.addEventListener(net.user1.orbiter.RoomEvent.REMOVE_OCCUPANT, removeOccupantListener);  
    chatRoom.addEventListener(net.user1.orbiter.AttributeEvent.UPDATE, roomAttributeUpdateListener);
    msgManager.addMessageListener(UPC.JOINED_ROOM, joinedRoomListener, this);
    chatRoom.join();
//    chatRoom.setAttribute(net.user1.orbiter.Tokens.REMOVE_ON_EMPTY_ATTR, 'false');
    
}

// Triggered when the connection to Union Server is closed
function closeListener (e) {
    connectionStatus = 0;
    setStatus("Rozłączono.", 'status', 2);
    displayChatMessage('Rozłączono.', 'status', 2);
    // Stop drawing content sent by other users
    clearInterval(processDrawingCommandsIntervalID);
}

function connectionErrorListener(e) {
    connectionStatus = 0;
    setStatus("Nastąpił problem.", 'status', 2);
    displayChatMessage('Nastąpił problem.', 'status', 2);
}

// Triggered when this client has joined the server-side drawing room
function joinedRoomListener (roomID) {
    //orbiter.getRoomManager().getRoom(roomID).setAttribute(net.user1.orbiter.Tokens.REMOVE_ON_EMPTY_ATTR, 'false');
   
    var appRoom = orbiter.getRoomManager().getRoom(roomID), isOpen = appRoom.getAttribute("_ROOM_IS_OPEN");
    // Display the shared application state
    if (isOpen == null || isOpen == 'true') {
        openLesson();
    // displayChatMessage("Zajęcia są otwarte.", 'status', 1);
    } else {
        displayChatMessage("Zajęcia są zamknięte.", 'status', -1);
        closeLesson();
    }
  
    // Update the shared application state...
    // Set lastOccupantJoinedAt to the approximate current time on the server
    appRoom.setAttribute("lastOccupantJoinedAt", orbiter.getServer().getServerTime().toString());
    // Add one to the numOccupantsLifetime attribute
    appRoom.setAttribute("numOccupantsLifetime", "%v+1", true, false, true);
    
     
    // Periodically execute drawing commands sent by other users
    processDrawingCommandsIntervalID = setInterval(processDrawingCommands, 20);
}

// Triggered when this client is informed that number of users in the 
// server-side drawing room has changed
function roomOccupantCountUpdateListener (roomID, numOccupants) {
    numOccupants = parseInt(numOccupants);
    if (numOccupants == 1) {
        setStatus("Nie ma nikogo więcej przy tablicy");
    } else if (numOccupants == 2) {
        setStatus("Obecnie rysujesz jeszcze z jedną osobą");
    } else {
        setStatus("Obecnie rysujesz jeszcze z " + (numOccupants-1) + " osobami");
    }
}

//==============================================================================
// ROOM EVENT LISTENERS
//==============================================================================
// Triggered when a client joins the room
function addOccupantListener (e) {
    setTimeout(function () {
        if(e.client.account) {
            var userLogin = e.getClient().getAccount().getUserID();
            addListOption(userLogin, e.getClientID());
        }
    }, delayInUserIDEnquire);
}
  
// Triggered when a client leaves the room
function removeOccupantListener (e) {
    removeListOption(e.getClientID());
    
    if(e.client.account)
        displayChatMessage("Użytkownik " + e.getClient().getAccount().getUserID() + " opuścił chat.", 'status', 0);
}

function roomAttributeUpdateListener (e) {
    var appRoom = orbiter.getRoomManager().getRoom(pl.edu.libratus.orbiter.room.id);
    // If the room's attributes change after it has been synchronized...
    if (appRoom.getSyncState() == net.user1.orbiter.SynchronizationState.SYNCHRONIZED) {
        // ...and the client that changed the attribute was *not* the current client
        if (e.getChangedAttr().byClient != orbiter.self()) {
            // ...then display a message for the changed attribute
            if (e.getChangedAttr().name == "_ROOM_IS_OPEN") {
                var isOpen = appRoom.getAttribute("_ROOM_IS_OPEN");
                if(isOpen == null || isOpen == 'true') {
                    openLesson();
                    displayChatMessage("Zajęcia zostały otwarte", 'status', 1);
                } else {
                    displayChatMessage("Zajęcia zostały zamknięte", 'status', -1);
                    closeLesson();
                }
            }
        }
    }
}

//==============================================================================
// ACCOUNT EVENT LISTENERS
//==============================================================================
// Triggered when the result of an account creation attempt is received
function createAccountResultListener (e) {
    orbiter.getLog().info("[CHAT] Create account result for [" + e.getUserID() + "]: " + e.getStatus());
    orbiter.getAccountManager().login(pl.edu.libratus.orbiter.credential.login, pl.edu.libratus.orbiter.credential.password);
}
  
// Triggered when the result of a login attempt is received
function loginResultListener (e) {
    orbiter.getLog().info("[CHAT] Login result for [" + e.getUserID() + "]: " + e.getStatus());
}
//==============================================================================
// SELECT MANAGEMENT
//==============================================================================
function addListOption (name, value) {
    var option, isAllreadySet = false;
    option = document.createElement("option");
    option.text  = name;
    option.value = value;
    
    for(var i = 0; i < occupantlist.length; ++i) {
        if(occupantlist[i].text == name) {
            isAllreadySet = true;
        }
    }
    
    if(!isAllreadySet) {
        occupantlist.add(option);
    }
    
    try {
        var el = document.getElementById('login_' + name);
        if(el) {
            el.setAttribute('class', 'active');
        }
    } catch(e) {}
}

function removeListOption (value) {
    for (var i = 0; i < occupantlist.length; i++) {
        if (occupantlist.options[i].value == value) {
            try {
                var el = document.getElementById('login_' + occupantlist.options[i].innerHTML);
                if(el) {
                    el.setAttribute('class', 'notactive');
                }
            } catch(e) {}
            
            occupantlist.remove(i);
            return;
        }
    }
}

//==============================================================================
// HANDLE INCOMING CLIENT ATTRIBUTES
//==============================================================================
// Triggered when Union Server sends a "snapshot" describing the drawing room,
// including a list of users supplied as unnamed arguments after the 
// roomAttributes parameter. For a description of roomSnapshotListener()'s 
// parameters, see "u54" in the UPC specification, 
// at: http://unionplatform.com/specs/upc/. This client receives the room 
// snapshot automatically when it the joins the drawing room.
function roomSnapshotListener (requestID,
    roomID,
    occupantCount,
    observerCount,
    roomAttributes) {
    // The unnamed arguments following 'roomAttributes' is a list of 
    // clients in the room. Assign that list to clientList. 
    var clientList = Array.prototype.slice.call(arguments).slice(5);
    var clientID;
    var roomAttrString;
    var roomAttrs;
    var attrName;
    var attrVal;
    
    // Loop through the list of clients in the room to get each client's
    // "thickness" and "color" attributes.
    for (var i = 0; i < clientList.length; i+=5) {
        clientID = clientList[i];
        
        // Each client's room-scoped client attributes are passed as a 
        // pipe-delimited string. Split that string to get the attributes.
        clientAttrString = clientList[i+4];
        clientAttrs = clientAttrString == "" ? [] : clientAttrString.split("|");
    
        // Pass each client attribute to processClientAttributeUpdate(), which will
        // check for the "thickness" and "color" attributes.
        for (var j = 0; j < clientAttrs.length; j++) {
            attrName = clientAttrs[j];
            attrVal  = clientAttrs[j+1];
            processClientAttributeUpdate(clientID, attrName, attrVal);
        }
    }
}

// Triggered when one of the clients in the drawing room changes an attribute
// value. When an attribute value changes, check to see whether it was either 
// the "thickness" attribute or the "color" attribute.
function clientAttributeUpdateListener (attrScope, 
    clientID,
    userID,
    attrName,
    attrVal,
    attrOptions) { 
    if (attrScope == pl.edu.libratus.orbiter.room.id) {
        processClientAttributeUpdate(clientID, attrName, attrVal);
    }
}

// Triggered when another client joins the chat room
function clientAddedToRoomListener (roomID, clientID, userID) {
    setTimeout(function () {
        var client = orbiter.clientMan.getClient(clientID);
        
        if(typeof client == 'object') {
            displayChatMessage("Użytkownik " + client.getAccount().getUserID() + " dołączył do chat-u.", 'status', 1);
        } else {
            displayChatMessage("Użytkownik " + clientID + " dołączył do chatu.", 'status', 1);
        }
    }, delayInUserIDEnquire);
}

// Triggered when a clients leaves the drawing room.
function clientRemovedFromRoomListener (roomID, clientID) {
    // The client is gone now, so remove all information pertaining to that client
    delete userThicknesses[clientID];
    delete userColors[clientID];
    // delete userCommands[clientID];
    delete userCurrentPositions[clientID];
    
//displayChatMessage("Użytkownik " + clientID + " opuścił chat.");
}

// Checks for changes to the the "thickness" and "color" attributes.
function processClientAttributeUpdate (clientID, attrName, attrVal) {
    if (attrName == Attributes.THICKNESS) {
        // The "thickness" attribute changed, so push a "set thickness" command
        // onto the drawing command stack for the specified client. But first, 
        // bring the thickness into legal range if necessary (prevents thickness hacking).
        addDrawingCommand(clientID, DrawingCommands.SET_THICKNESS, getValidThickness(attrVal));
    } else if (attrName == Attributes.COLOR) {
        // The "color" attribute changed, so push a "set color" command
        // onto the drawing command stack for the specified client
        addDrawingCommand(clientID, DrawingCommands.SET_COLOR, attrVal);
    } else if (attrName == Attributes.TOOL) {
        // The "color" attribute changed, so push a "set color" command
        // onto the drawing command stack for the specified client
        addDrawingCommand(clientID, DrawingCommands.SET_TOOL, attrVal);
    }
}

//==============================================================================
// HANDLE INCOMING CLIENT MESSAGES
//==============================================================================

// Triggered when a remote client sends a "MOVE" message to this client
function moveMessageListener (fromClientID, coordsString) {
    if(localTool == Tools.TEXT) {
        return;
    }
    // Parse the specified (x, y) coordinate
    var coords = coordsString.split(",");
    var position = {
        x:parseInt(coords[0]), 
        y:parseInt(coords[1])
    };
    // Push a "moveTo" command onto the drawing-command stack for the sender
    addDrawingCommand(fromClientID, DrawingCommands.MOVE_TO, position);
}

// Triggered when a remote client sends a "PATH" message to this client
function pathMessageListener (fromClientID, pathString) {
    // Parse the specified list of points
    var path = pathString.split(",");
  
    // For each point, push a "lineTo" command onto the drawing-command stack 
    // for the sender
    var position;
    for (var i = 0; i < path.length; i+=2) {
        position = {
            x:parseInt(path[i]), 
            y:parseInt(path[i+1])
        };
        addDrawingCommand(fromClientID, DrawingCommands.LINE_TO, position);
    }
}

// Triggered when a remote client sends a "TEXT" message to this client
function textMessageListener (fromClientID, textString) {
    var opt = textString.split(',');
    
    var opt = {
        x: opt[0],
        y: opt[1],
        text: opt[2]
    }
    addDrawingCommand(fromClientID, DrawingCommands.TEXT, opt);
}

// Triggered when a remote client sends a "UNDO" message to this client
function undoMessageListener (fromClientID) {
    addDrawingCommand(fromClientID, DrawingCommands.UNDO);
}

// Triggered when a remote client sends a "CLEAR" message to this client
function clearMessageListener (fromClientID) {
    addDrawingCommand(fromClientID, DrawingCommands.CLEAR);
}



//==============================================================================
// CHAT SENDING AND RECEIVING
//==============================================================================
// Sends a chat message to everyone in the chat room
function sendMessage () {
    var outgoing = document.getElementById("outgoing");
    if (outgoing.value.length > 0) {
        msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, "CHAT_MESSAGE", pl.edu.libratus.orbiter.room.id, "true", "", outgoing.value);
        outgoing.value = "";
        // Focus text field again after submission (required for IE8 only)
        setTimeout(function () {
            outgoing.focus();
        }, 10);
    }
}

// Triggered when a chat message is received
function chatMessageListener (fromClientID, message) {
    if(fromClientID && typeof fromClientID == 'object') {
        var userLogin = fromClientID.account.getUserID();
        if(userLogin) {
            displayChatMessage(message, 'msg', fromClientID.account.getUserID());
        } else {
            displayChatMessage(message, 'msg', "Użytkownik" + fromClientID.getClientID());
        }
    } else {
        displayChatMessage(message, 'msg', "Użytkownik" + fromClientID);
    }
}

// Triggered when a chat message is received
function systemMessageListener (fromClientID, message) {
    displayChatMessage(message, 'system', fromClientID.account.getUserID());
}

function historyMessageListener (fromClientID, message) {
    if(!retriveHistory) {
        return;
    } else if(message == '<H:end></H:end>') {
        endHistory();
        return;
    }
    
    if(!canvasLocker) {
        canvasLocker = document.createElement('div');
        canvasLocker.id = 'canvasLocker'
        canvasWrapper.appendChild(canvasLocker);
    }
    
    message = message.replace(/<H:U>/g, "<U>");
    message = message.replace(/<\/H:U>/g, "</U>");
    message = message.replace(/<H:M>/g, "<M>");
    message = message.replace(/<\/H:M>/g, "</M>");
    message = message.replace(/<H:L>/g, "<L>");
    message = message.replace(/<\/H:L>/g, "</L>");
    message = message.replace(/<H:A>/g, "<A>");
    var upc = message.replace(/<\/H:A>/g, "</A>");

    var method;
    var upcArgs = new Array();
  
    var closeMTagIndex = upc.indexOf("</M>");
    method = upc.substring(6, closeMTagIndex);
  
    var searchBeginIndex = upc.indexOf("<A>", closeMTagIndex);
    var closeATagIndex, closeTagIndex;
    var arg;
    while (searchBeginIndex != -1) {
        closeATagIndex = upc.indexOf("</A>", searchBeginIndex);
        arg = upc.substring(searchBeginIndex+3, closeATagIndex);
        if (arg.indexOf("<![CDATA[") == 0) {
            arg = arg.substr(9, arg.length-12);
        }
        upcArgs.push(arg);
        searchBeginIndex = upc.indexOf("<A>", closeATagIndex);
    }     
    
    searchBeginIndex = upc.indexOf("<H:clientID>", closeMTagIndex);
    closeTagIndex = upc.indexOf("</H:clientID>", searchBeginIndex);
    
    fromClientID = upc.substring(searchBeginIndex+12, closeTagIndex);
    
    searchBeginIndex = upc.indexOf("<H:userID>", closeMTagIndex);
    closeTagIndex = upc.indexOf("</H:userID>", searchBeginIndex);
    
    var fromUserID = upc.substring(searchBeginIndex+10, closeTagIndex);
    
    if(method == 'u3') {
        var upcArgs1 = [upcArgs[4], upcArgs[0], fromUserID, upcArgs[2], upcArgs[3], upcArgs[5]];
        //<U><M>u8</M><L><A><![CDATA[pl.edu.libratus.room.dojo]]></A><A>24</A><A><![CDATA[Julek]]></A><A><![CDATA[thickness]]></A><A><![CDATA[30]]></A><A>4</A></L></U>
        //Setting attribute [pl.edu.libratus.room.dojo.thickness]. New value: [25]. Old value: [19]. 
        method = 'u8';
        upcArgs = upcArgs1;
    } else {
        method = 'u7';
        upcArgs[2] = fromClientID;
        upcArgs[3] = fromUserID;
    }
    
    var client = null;
    try {
        client = orbiter.clientMan.getClientByUserID(fromUserID);
    } catch(e) {
        
    }
    
    if(!client) {
        //if client not set then manualy set client:
        client = new net.user1.orbiter.Client(fromClientID, orbiter.clientMan, orbiter.clientMan.messageManager, orbiter.clientMan.roomManager, orbiter.clientMan.connectionManager, orbiter.clientMan.server, orbiter.clientMan.log);
        client.setAccount(orbiter.accountMan.requestAccount(fromUserID));
    
        orbiter.clientMan.lifetimeClientsRequested++;
        orbiter.clientMan.clientCache.put(fromClientID, client);
    }
    
    //upcArgs[2] = fromClientID;
    
    try {
        orbiter.messageMan.notifyMessageListeners(method, upcArgs);
    } catch (e) {
       // console.log(e);
        listenerError = e;
    }
  
}

function endHistory() {
    retriveHistory = false;
    
    addDrawingCommand (0, DrawingCommands.END_HISTORY, null);
}

// Displays a single chat message
function displayChatMessage (message, status, value) {
    // Make the new chat message element
    var msg = document.createElement("div");
    
    msg.appendChild(document.createTextNode(message));
    msg.appendChild(document.createElement("br"));
    msg.style.borderTop = '1px solid #ddd';
    
    if(status) {
        switch(status) {
            case 'msg':
                var now = new Date(), month = now.getMonth()+1, day = now.getDay(), hours = now.getHours(), minutes = now.getMinutes(), seconds = now.getSeconds(), date;
                if(month < 10) {
                    month = '0' + month;
                }
                if(day < 10) {
                    day = '0' + day;
                }
                if(hours < 10) {
                    hours = '0' + hours;
                }
                if(minutes < 10) {
                    minutes = '0' + minutes;
                }
                if(seconds < 10) {
                    seconds = '0' + seconds;
                }
                date = now.getFullYear() + '-' + month + '-' + day;
                now = hours + ':' + minutes + ':' + seconds; 
                
                msg.innerHTML = '<b>' + value + '</b> <small style="color: gray;">[' + now + ']</small>: ' + msg.innerHTML;
                break;
            case 'system':
                msg.style.color = 'gray';
                break;
            case 'status':
                if(value) {
                    msg.style.color = 'green';
                    if(value > 1) {
                        msg.style.color = 'blue';
                    } else if(value < 0) {
                        msg.style.color = 'red';
                    }
                } else {
                    msg.style.color = 'orange';
                }
                break;
            default:
                break;
        }
    }

    // Append the new message to the chat
    var chatPane = document.getElementById("chatPane");
    chatPane.appendChild(msg);
  
    // Trim the chat to 500 messages
    if (chatPane.childNodes.length > 500) {
        chatPane.removeChild(chatPane.firstChild);
    }
    chatPane.scrollTop = chatPane.scrollHeight;
}

//==============================================================================
// BROADCAST DRAWING DATA TO OTHER USERS
//==============================================================================
// Sends the local user's drawing-path information to other users in the 
// drawing room.
function broadcastPath () {
    if(localTool == Tools.TEXT) {
        bufferedPath = [];
        return;
    }
    
    // If there aren't any points buffered (e.g., if the pen is down but not
    // moving), then don't send the PATH message.
    if (bufferedPath.length == 0) {
        return;
    }
    // Use SEND_MESSAGE_TO_ROOMS to deliver the message to all users in the room
    // Parameters are: messageName, roomID, includeSelf, filters, ...args. For
    // details, see http://unionplatform.com/specs/upc/.
    msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, 
        Messages.PATH, 
        pl.edu.libratus.orbiter.room.id, 
        "false", 
        "", 
        bufferedPath.join(","));
    // Clear the local user's outgoing path data
    bufferedPath = [];
    // If the user is no longer drawing, stop broadcasting drawing information
    if (!isPenDown) {
        clearInterval(broadcastPathIntervalID);
    }
}

// Sends all users in the drawing room an instruction to reposition the local
// user's pen.
function broadcastMove (x, y) {
    if(localTool == Tools.TEXT) {
        return;
    }
    
    // We want to store the state of the canvas, before we apply the change on it. That way we can
    // revert back to the state before the canvas was changed.
    saveRestorePoint();
    
    msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, 
        Messages.MOVE, 
        pl.edu.libratus.orbiter.room.id, 
        "false", 
        "", 
        x + "," + y);
}

// Sends the local user's drawing-path information to other users in the 
// drawing room.
function broadcastText (text, x, y) {
    // If there aren't any points buffered (e.g., if the pen is down but not
    // moving), then don't send the PATH message.
    if (!text) {
        return;
    }
    // Use SEND_MESSAGE_TO_ROOMS to deliver the message to all users in the room
    // Parameters are: messageName, roomID, includeSelf, filters, ...args. For
    // details, see http://unionplatform.com/specs/upc/.
    msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, 
        Messages.TEXT, 
        pl.edu.libratus.orbiter.room.id, 
        "false", 
        "", 
        x + "," + y + "," + text);
// Clear the local user's outgoing path data
}

// Sends all users in the drawing room an instruction undo last operation
function broadcastUndo () {
    // If we have some restore points
    if (restorePoints.length > 0) {
        msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, 
            Messages.UNDO, 
            pl.edu.libratus.orbiter.room.id, 
            "false", 
            "", 
            "");
    }
}

// Sends all users in the drawing room an instruction clear last operation
function broadcastClear () {
    saveRestorePoint();
    
    // If we have some restore points
    if (restorePoints.length > 0) {
        msgManager.sendUPC(UPC.SEND_MESSAGE_TO_ROOMS, 
            Messages.CLEAR, 
            pl.edu.libratus.orbiter.room.id, 
            "false", 
            "", 
            "");
    }
}

//==============================================================================
// PROCESS DRAWING COMMANDS FROM OTHER USERS
//==============================================================================
// Pushes a drawing command onto the command stack for the specified client.
// At a regular interval, commands are pulled off the stack and executed,
// causing remote user's drawings to appear on-screen. 
function addDrawingCommand (clientID, commandName, arg) {
    var id;
    if(typeof clientID == 'object') {
        id = clientID.getClientID();
    } else {
        id = clientID;
    }
    
    //    // If this client does not yet have a command stack, make one. 
    //    if (userCommands[id] == undefined) {
    //        userCommands[id] = [];
    //    }
    // Push the command onto the stack.
    var command = {};
    command["commandName"] = commandName;
    command["arg"] = arg;
    command["clientID"] = id;
    
    //    userCommands[id].push(command);
    userCommands.push(command);
}

// Executes the oldest command on all user's command stacks
function processDrawingCommands () {
    var command;
    // Loop over all command stacks
    while (command = userCommands.shift()) {
        var clientID = command.clientID;
        //console.log(clientID, command.commandName);
        
        switch (command.commandName) {
            case DrawingCommands.MOVE_TO:
                saveRestorePoint();
                
                userCurrentPositions[clientID] = {
                    x:command.arg.x, 
                    y:command.arg.y
                };
                break;
        
            case DrawingCommands.LINE_TO:
                if (userCurrentPositions[clientID] == undefined) {
                    userCurrentPositions[clientID] = {
                        x:command.arg.x, 
                        y:command.arg.y
                    };
                } else {
                    var lineColor = userColors[clientID] || defaultLineColor;
                    if(userTools[clientID] == Tools.ERASER) {
                        lineColor = pl.edu.libratus.orbiter.whiteboard.canvas.color;
                    }
                    
                    drawLine(lineColor, 
                        userThicknesses[clientID] || defaultLineThickness, 
                        userCurrentPositions[clientID].x, 
                        userCurrentPositions[clientID].y,
                        command.arg.x, 
                        command.arg.y);
                    userCurrentPositions[clientID].x = command.arg.x; 
                    userCurrentPositions[clientID].y = command.arg.y; 
                }
                break;
            case DrawingCommands.TEXT:
                drawText(
                    userColors[clientID] || defaultLineColor, 
                    userThicknesses[clientID] || defaultLineThickness, 
                    command.arg.text, command.arg.x, command.arg.y);
                break;
            case DrawingCommands.UNDO:
                undoDrawOnCanvas();
                return;
                break;
            case DrawingCommands.CLEAR:
                saveRestorePoint();
                clearCanvas();
                break;
            case DrawingCommands.SET_THICKNESS:
                userThicknesses[clientID] = command.arg;
                break;
        
            case DrawingCommands.SET_COLOR:
                userColors[clientID] = command.arg;
                break;
            case DrawingCommands.SET_TOOL:
                userTools[clientID] = command.arg;
                break;
            case DrawingCommands.END_HISTORY:
                canvasWrapper.removeChild(canvasLocker);
                break;
        }
    }
}

//==============================================================================
// RESTORATION
//==============================================================================
// The function which saves the restoration points
function saveRestorePoint() {
    // Get the current canvas drawing as a base64 encoded value
    var imgSrc = canvas.toDataURL("image/png");

    // and store this value as a 'restoration point', to which we can later revert
    restorePoints.push(imgSrc);
}
// Function to restore the canvas from a restoration point
function undoDrawOnCanvas() {
    // If we have some restore points
    if (restorePoints.length > 0) {
        // Create a new Image object
        var oImg = new Image();
        // When the image object is fully loaded in the memory...
        oImg.onload = function() {
            //            document.body.innerHTML = '';
            //            document.body.appendChild(oImg);
            //            return;
            clearCanvas();
            // Get the canvas context
            context.drawImage(oImg, 0, 0);
            // Periodically execute drawing commands sent by other users
            processDrawingCommandsIntervalID = setInterval(processDrawingCommands, 20);
        }
        
        clearInterval(processDrawingCommandsIntervalID);
        //        processDrawingCommandsIntervalID = null;
        //        setTimeout(function () { //if it take to long then stop it;
        //            if(processDrawingCommandsIntervalID == null) {
        //                processDrawingCommandsIntervalID = setInterval(processDrawingCommands, 20);
        //            }
        //        }, 2000);
        // The source of the image, is the last restoration point
        oImg.src = restorePoints.pop();
        
    }
}

function clearCanvas() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    var w = canvas.width;
    canvas.width = 1;
    canvas.width = w;
    
    context.lineCap = "round";
}

//==============================================================================
// TOUCH-INPUT EVENT LISTENERS
//==============================================================================
// On devices that support touch input, this function is triggered when the 
// user touches the screen.
function touchDownListener (e) {
    // Note that this device supports touch so that we can prevent conflicts with
    // mouse input events.
    hasTouch = true;
    // Prevent the touch from scrolling the page, but allow interaction with the
    // control-panel menus. The "event.target.nodeName" variable provides the name
    // of the HTML element that was touched.
    if (event.target.nodeName != "SELECT") {
        e.preventDefault();
    }
    
    var scrollY = pageYOffset,
    scrollX = pageXOffset;
    
    if(document.documentElenent && document.documentElenent.scrollTop) {
        scrollY = document.documentElenent.scrollTop;
        scrollX = document.documentElement.scrollLeft;
    }
    
    // Determine where the user touched screen.
    var touchX = e.changedTouches[0].clientX - canvas.offsetLeft - canvasWrapper.offsetLeft + scrollX;
    var touchY = e.changedTouches[0].clientY - canvas.offsetTop - canvasWrapper.offsetTop + scrollY;
    // A second "touch start" event may occur if the user touches the screen with
    // two fingers. Ignore the second event if the pen is already down.
    if (!isPenDown) {
        // Move the drawing pen to the position that was touched
        penDown(touchX, touchY);
    }
}

// On devices that support touch input, this function is triggered when the user
// drags a finger across the screen.
function touchMoveListener (e) {
    hasTouch = true;
    e.preventDefault();
    
    var scrollY = pageYOffset,
    scrollX = pageXOffset;
    
    if(document.documentElenent && document.documentElenent.scrollTop) {
        scrollY = document.documentElenent.scrollTop;
        scrollX = document.documentElement.scrollLeft;
    }
    
    var touchX = e.changedTouches[0].clientX - canvas.offsetLeft - canvasWrapper.offsetLeft + scrollX;
    var touchY = e.changedTouches[0].clientY - canvas.offsetTop - canvasWrapper.offsetTop + scrollY;
    // Draw a line to the position being touched.
    penMove(touchX, touchY);
}

// On devices that support touch input, this function is triggered when the 
// user stops touching the screen.
function touchUpListener () {
    // "Lift" the drawing pen, so lines are no longer drawn
    penUp();
}

function inputKeypressListener(e) {
    if(e.keyCode == 13) {//key 'enter' pressed
        removeTextInput();
    }
}

//==============================================================================
// MOUSE-INPUT EVENT LISTENERS
//==============================================================================
// Triggered when the mouse is pressed down
function pointerDownListener (e) {
    // If this is an iPhone, iPad, Android, or other touch-capable device, ignore
    // simulated mouse input.
    if (hasTouch) {
        return;
    }
  
    var scrollY = pageYOffset,
    scrollX = pageXOffset;
    
    if(document.documentElenent && document.documentElenent.scrollTop) {
        scrollY = document.documentElenent.scrollTop;
        scrollX = document.documentElement.scrollLeft;
    }
    
    // Retrieve a reference to the Event object for this mousedown event.
    // Internet Explorer uses window.event; other browsers use the event parameter
    var event = e || window.event; 
    // Determine where the user clicked the mouse.
    var mouseX = event.clientX - canvas.offsetLeft - canvasWrapper.offsetLeft + scrollX;
    var mouseY = event.clientY - canvas.offsetTop - canvasWrapper.offsetTop + scrollY;
  
    // Move the drawing pen to the position that was clicked
    penDown(mouseX, mouseY);
  
    // We want mouse input to be used for drawing only, so we need to stop the 
    // browser from/ performing default mouse actions, such as text selection. 
    // In Internet Explorer, we "prevent default actions" by returning false. In 
    // other browsers, we invoke event.preventDefault().
    if (event.preventDefault) {
        if (event.target.nodeName != "SELECT") {
            event.preventDefault();
        }
    } else {
        return false;  // IE
    }
}

// Triggered when the mouse moves
function pointerMoveListener (e) {
    if (hasTouch) {
        return;
    }
    
    var scrollY = pageYOffset,
    scrollX = pageXOffset;
    
    if(document.documentElenent && document.documentElenent.scrollTop) {
        scrollY = document.documentElenent.scrollTop;
        scrollX = document.documentElement.scrollLeft;
    }
    
    var event = e || window.event; // IE uses window.event, not e
    var mouseX = event.clientX - canvas.offsetLeft - canvasWrapper.offsetLeft + scrollX;
    var mouseY = event.clientY - canvas.offsetTop - canvasWrapper.offsetTop + scrollY;
  
    // Draw a line if the pen is down
    penMove(mouseX, mouseY);

    // Prevent default browser actions, such as text selection
    if (event.preventDefault) {
        event.preventDefault();
    } else {
        return false;  // IE
    }
}

// Triggered when the mouse button is released
function pointerUpListener (e) {
    if (hasTouch) {
        return;
    }
    // "Lift" the drawing pen
    penUp();
}

function keyDownListener(e) {
    if(e.ctrlKey && e.keyCode == 90) {//CTRL + Z
        undo();
    }
}

function undo() {
    broadcastUndo();
    undoDrawOnCanvas();
}

function clearWhiteboard() {
    broadcastClear();
    clearCanvas();
}

//==============================================================================
// CONTROL PANEL MENU-INPUT EVENT LISTENERS
//==============================================================================
// Triggered when an option in the "line thickness" menu is selected
function thicknessSelectListener (e) {
    // Determine which option was selected
    var newThickness = getValidThickness(this.value);//options[this.selectedIndex].value;
    // Locally, set the line thickness to the selected value
    
    switch(localTool) {
        case Tools.ERASER:
            localEraserThickness = newThickness;
            break;
        case Tools.PENCIL:
            localLineThickness = newThickness;
            break;
        case Tools.TEXT:
            var diff = localTextSize - newThickness;
            localTextSize = newThickness;
            textInput.style.fontSize = localTextSize + 'px';
            textInput.style.top = parseInt(textInput.style.top) + diff + 'px';
            break;
        default:
            alert('nieznane narzędzie');
            break;
    }
    
    sendThickness(newThickness);
    // After the user selects a value in the drop-down menu, the iPhone
    // automatically scrolls the page, so scroll back to the top-left. 
    iPhoneToTop();
}

function sendThickness(thickness) {
    // Share the selected thickness with other users by setting the client
    // attribute named "thickness". Attributes are automatically shared with other 
    // clients in the room, triggering clientAttributeUpdateListener(). 
    // Arguments for SET_CLIENT_ATTR are:
    //   clientID 
    //   userID (None in this case)
    //   attrName 
    //   escapedAttrValue
    //   attrScope (The room) 
    //   attrOptions (An integer whose bits specify options. "4" means 
    //                the attribute should be shared).
    msgManager.sendUPC(UPC.SET_CLIENT_ATTR, 
        orbiter.getClientID(),
        "",
        Attributes.THICKNESS,
        thickness,
        pl.edu.libratus.orbiter.room.id,
        "4");
}

// Triggered when an option in the "line color" menu is selected
function colorSelectListener (e) {
    // Determine which option was selected
    var newColor = this.value;//options[this.selectedIndex].value;
    // Locally, set the line color to the selected value
    localLineColor = newColor;
    textInput.style.color = localLineColor;
    
    // Share selected color with other users
    msgManager.sendUPC(UPC.SET_CLIENT_ATTR, 
        orbiter.getClientID(),
        "",
        Attributes.COLOR,
        newColor,
        pl.edu.libratus.orbiter.room.id,
        "4");

    // Scroll the iPhone back to the top-left. 
    iPhoneToTop();
}

// Triggered when an option in the "tool" menu is selected
function toolSelectListener (e) {
    // Determine which option was selected
    var newTool = this.value;//options[this.selectedIndex].value;
    // Locally, set the line thickness to the selected value
    localTool = getValidTool(newTool);
    
    if(pl.edu.libratus.orbiter.whiteboard.tool.cursor[localTool]) {
        canvas.setAttribute('class', pl.edu.libratus.orbiter.whiteboard.tool.cursor[localTool]);
    }
    
    // Share the selected thickness with other users by setting the client
    // attribute named "thickness". Attributes are automatically shared with other 
    // clients in the room, triggering clientAttributeUpdateListener(). 
    // Arguments for SET_CLIENT_ATTR are:
    //   clientID 
    //   userID (None in this case)
    //   attrName 
    //   escapedAttrValue
    //   attrScope (The room) 
    //   attrOptions (An integer whose bits specify options. "4" means 
    //                the attribute should be shared).
    msgManager.sendUPC(UPC.SET_CLIENT_ATTR, 
        orbiter.getClientID(),
        "",
        Attributes.TOOL,
        localTool,
        pl.edu.libratus.orbiter.room.id,
        "4");
    
    var thickness = document.getElementById("thickness");
    
    switch(localTool) {
        case Tools.ERASER:
            thickness.value = localEraserThickness;
            break;
        case Tools.PENCIL:
            thickness.value = localLineThickness;
            break;
        case Tools.TEXT:
            thickness.value = localTextSize;
            break;
        default:
            alert('nieznane narzędzie');
            break;
            
                
            
    }
    thickness.onchange();        
    jQuery.fn.thicknessPicker.changeThickness(thickness.value);
    
    // After the user selects a value in the drop-down menu, the iPhone
    // automatically scrolls the page, so scroll back to the top-left. 
    iPhoneToTop();
}

//==============================================================================
// INPUT TEXT
//==============================================================================
function putTextInput(x, y) {
    textInput.value = '';
    canvasWrapper.appendChild(textInput);
    textInput.style.top = (y + 36 - localTextSize) + 'px';
    textInput.style.left = x + 'px';
        
    textInput.focus();
}

function removeTextInput() {
    if(textInput.value) {
        drawText(localLineColor, localTextSize, textInput.value, localPen.x, localPen.y);
        
        broadcastText(textInput.value, localPen.x, localPen.y);
    }
    
    try {
        canvasWrapper.removeChild(textInput);
        textInput.value = '';
    } catch(e) {}
}
//==============================================================================
// PEN
//==============================================================================
// Places the pen in the specified location without drawing a line. If the pen
// subsequently moves, a line will be drawn.
function penDown (x, y) {

    //insert textInput
    if(localTool == Tools.TEXT) {
        removeTextInput();
        putTextInput(x, y);
    }
    
    isPenDown = true;
    localPen.x = x;
    localPen.y = y;
  
    // Send this user's new pen position to other users.
    broadcastMove(x, y);
    
    
  
    // Begin sending this user's drawing path to other users every 500 milliseconds.
    broadcastPathIntervalID = setInterval(broadcastPath, 200);
}

// Draws a line if the pen is down.
function penMove (x, y) { 
    if (isPenDown) {
        // Buffer the new position for broadcast to other users. Buffer a maximum
        // of 100 points per second.
        if ((new Date().getTime() - lastBufferTime) > 10) {
            bufferedPath.push(x + "," + y);
            lastBufferTime = new Date().getTime();
        }
    
        var lineColor = localLineColor, lineThickness = localLineThickness;
        
        switch(localTool) {
            case Tools.ERASER:
                lineColor = pl.edu.libratus.orbiter.whiteboard.canvas.color;
                lineThickness = localEraserThickness;
            case Tools.PENCIL:
                // Draw the line locally.
                drawLine(lineColor, lineThickness, localPen.x, localPen.y, x, y);
                break;
            case Tools.TEXT:
                break;
            default:
                alert('złe narzędzie');
                break;
        }
        
    
        // Move the pen to the end of the line that was just drawn.
        localPen.x = x;
        localPen.y = y;
    }
}

// "Lifts" the drawing pen, so that lines are no longer draw when the mouse or
// touch-input device moves.
function penUp () {
    isPenDown = false;
}

//==============================================================================
// DRAWING
//==============================================================================
// Draws a line on the HTML5 canvas
function drawLine (color, thickness, x1, y1, x2, y2) {

    context.strokeStyle = color;
    context.lineWidth   = thickness;
  
    context.beginPath();
    context.moveTo(x1, y1)
    context.lineTo(x2, y2);
    context.stroke();
}

function drawText(color, size, text, x, y) {
    // We want to store the state of the canvas, before we apply the change on it. That way we can
    // revert back to the state before the canvas was changed.
    saveRestorePoint();
    
    context.fillStyle = color;
    context.font= size + "px " + pl.edu.libratus.orbiter.whiteboard.font;
    context.fillText(text, parseInt(x) + 4, parseInt(y) + 14 - size/10);
}

//==============================================================================
// STATUS
//==============================================================================
// Updates the text of the on-screen HTML "status" div tag
function setStatus (message) {
    document.getElementById("status").innerHTML = message;
}

//==============================================================================
// IPHONE UTILS
//==============================================================================
// Hides the iPhone address bar by scrolling it out of view
function iPhoneToTop () {
    if (navigator.userAgent.indexOf("iPhone") != -1) {
        setTimeout (function () {
            window.scroll(0, 0);
        }, 100);
    }
}

//==============================================================================
// DATA VALIDATION
//==============================================================================
function getValidThickness (value) {
    value = parseInt(value);
    var thickness = isNaN(value) ? defaultLineThickness : value;
    return Math.max(1, Math.min(thickness, maxLineThickness));
}
function getValidTool (value) {
    if(!value) {
        return defaultTool;
    }
    
    for(i in Tools) {
        if(Tools[i] == value) {
            return value;
        }
    }
    
    return defaultTool;
}


//==============================================================================
// ADD FUNC
//==============================================================================
function selectTool(tool) {
    var toolSelect = document.getElementById('tool'), change = false;
    toolSelect.value = tool;
    
    for(i = 0; i < toolSelect.options.length; ++i) {
        if(toolSelect.options[i].value != tool) {
            document.getElementById(toolSelect.options[i].value).setAttribute('class', 'tool');
            toolSelect.options[i].removeAttribute('selected');
        } else {
            if(!toolSelect.options[i].getAttribute('selected')) {
                change = true;
            }
            toolSelect.options[i].setAttribute('selected', 'selected');
            toolSelect.selectedIndex = i;
        }
    }
    
    if(change) {
        if(tool != Tools.TEXT) {
            removeTextInput();
        }
        
        document.getElementById(tool).setAttribute('class', 'selectedTool');
        document.getElementById("tool").onchange();
    }
}