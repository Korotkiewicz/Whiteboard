package pl.edu.libratus.module.room.history;

import net.user1.union.api.*;
import net.user1.union.core.upc.UPCMessage;
import net.user1.union.core.context.ModuleContext;
import net.user1.union.core.def.AttributeDef;
import net.user1.union.core.attribute.Attribute;
import net.user1.union.core.event.RoomEvent;
import net.user1.union.core.event.UPCEvent;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.*;
import java.text.SimpleDateFormat;

import org.apache.log4j.Logger;

import javax.xml.parsers.*;
import org.xml.sax.InputSource;
import org.w3c.dom.*;
import java.io.*;

/**
 * Joins a chat room (see Your First Union Application at www.unionplatform.com)
 * and welcomes users, says goodbye, and occasionally adds to the conversation.
 */
public class HistoryRoomModule implements Module {

    private ModuleContext m_ctx;
    private String room_id;
//    private Room room = null;
    private static Logger s_log = Logger.getLogger(HistoryRoomModule.class);
    //Database variables:
    private String databaseLogin = "unionserver";
    private String databasePass = "BDfrjNXzfKxYMr4U";
    private Connection connect = null;
    private String query = "INSERT INTO room_message (room_id, type, value, client_id, user_id) VALUES (?,?,?,?,?)";
    private String queryFetch = "SELECT value, client_id, user_id FROM room_message WHERE room_id = ? AND date >= ? ORDER BY id ASC";
//    private Integer history = 1;
    private DocumentBuilderFactory dbf;
    private DocumentBuilder db;
    private InputSource is;
    private List<String> allowedTypes = new ArrayList<String>(Arrays.asList("MOVE", "PATH", "TEXT", "UNDO", "CLEAR", "CHAT_MESSAGE", "SYSTEM_MESSAGE", "tool", "thickness", "color"));
    private Integer default_showHistory = 1;
    //here you can determine how many days show in history (0 or false meen not show history at all)
    private Map<String, Integer> exception_showHistory = new HashMap<String, Integer>();

    public boolean init(ModuleContext ctx) {
        m_ctx = ctx;
        room_id = (String) m_ctx.getRoom().getQualifiedID();
///	room = m_ctx.getRoom();
        exception_showHistory.put("pl.edu.libratus.room.dojo", new Integer(0));

        s_log.debug("HistoryRoomModule: init");

        Integer history = getHistoryAttribute(m_ctx.getRoom());

        if (connectToDatabase()) {
            m_ctx.getServer().getUPCMessageProcessor().addEventListener(UPCEvent.UPC_PROCESSED, this, "onUPCMessage");
            if (history == null || history > 0) {
                m_ctx.getRoom().addEventListener(RoomEvent.ADD_CLIENT, this, "onAddClient");
            }
        } else {
            s_log.info("HistoryRoomModule: onModuleMessage has not been registered");
        }

        try {
            dbf = DocumentBuilderFactory.newInstance();
            db = dbf.newDocumentBuilder();
            is = new InputSource();
        } catch (Exception e) {
        }
        // --- register to receive notification when a client is added or removed to the room
//        m_ctx.getRoom().addEventListener(RoomEvent.ADD_CLIENT, this, "onAddClient");

        return true;
    }

    /**
     * This method is invoked when a module message has been sent by a client to
     * the room. We use it to get responses from clients that are connected to
     * this server. Responses collected by clients connected to other servers
     * will be collected by the remote event "SURVEY_RESULTS".
     */
    public void onUPCMessage(UPCEvent evt) {
        PreparedStatement statement = null;
        String type = null;
        String value;
        String client_id;
        String user_id;

        UPCProcessingRecord upcPR = evt.getUPCProcessingRecord();
        UPCMessage msg = upcPR.getUPC();
        s_log.debug("HistoryRoomModule: onUPCMessage " + evt + " msg: " + msg.toString());

        try {
            value = msg.toString();
            is.setCharacterStream(new StringReader(value));

            Document doc = db.parse(is);
            NodeList nodes = doc.getElementsByTagName("A");

            Node item = nodes.item(0);
            type = item.getTextContent();//"CHAT_MESSAGE";//evt.getMessage().getMessageName();

            if (type == null) {
                return;
            }

            item = nodes.item(2);

            if (item == null) {
                return;
            }

            String type1 = item.getTextContent();

            if (type1 != null && allowedTypes.contains(type1)) {
                type = type1;
            }

            if (allowedTypes.contains(type)) {
                client_id = upcPR.getClient().getClientID();
                user_id = upcPR.getClient().getUserID();

                statement = connect.prepareStatement(query);
                statement.setString(1, room_id);
                statement.setString(2, type);
                statement.setString(3, value);
                statement.setString(4, client_id);
                statement.setString(5, user_id);

                statement.executeUpdate();
                statement.close();
            }
        } catch (Exception e) {
            s_log.error("HistoryRoomModule: JDBC doesn't work correct! PrepareStatement failed: " + e.getMessage() + " upc type " + type);
        } finally {
            try {
                if (statement != null) {
                    statement.close();
                }
            } catch (Exception e) {
            }
        }
    }

    /**
     * This method is the callback for the event we specified in the init
     * method. It will be called whenever a client is added to the room.
     */
    public void onAddClient(RoomEvent evt) {
        Integer history = getHistoryAttribute(evt.getRoom());

        if ((history != null && history < 1) || !connectToDatabase()) {
            return;
        }

        Client clients[] = {evt.getClient()};
        Set<Client> set = new HashSet<Client>(Arrays.asList(clients));

        PreparedStatement statement = null;
        ResultSet res = null;

        Date dateNow = new Date();
        if (history != null && history > 1) {
            Calendar c = Calendar.getInstance();
            c.setTime(dateNow);
            c.add(Calendar.DATE, (history - 1) * -1);
            dateNow = c.getTime();
        }
        SimpleDateFormat dateformat = new SimpleDateFormat("yyyy-MM-dd 00:00:00");
        String dateString = dateformat.format(dateNow);

        String msg = null;
        String clientID = null;
        String userID = null;

        try {
            s_log.debug("HistoryRoomModule: onAddClient: prepare statement to fetch upc messages from date = " + dateString);

            statement = connect.prepareStatement(queryFetch);
            statement.setString(1, room_id);
            //statement.setString(1, room_id);
            statement.setString(2, dateString);
            res = statement.executeQuery();

            while (res.next()) {
                msg = res.getString("value");//"<U><M>u1</M><L><A>CHAT_MESSAGE</A><A>pl.edu.libratus.room.dojo</A><A>true</A><A></A><A>Witaj</A></L></U>";
                clientID = res.getString("client_id");//"150";
                userID = res.getString("user_id");//"aMichalKorotkiewicz";

                msg = msg.replaceAll("<U>", "<H:U>");
                msg = msg.replaceAll("</U>", "</H:U>");
                msg = msg.replaceAll("<M>", "<H:M>");
                msg = msg.replaceAll("</M>", "</H:M>");
                msg = msg.replaceAll("<L>", "<H:L>");
                msg = msg.replaceAll("</L>", "</H:L>");
                msg = msg.replaceAll("<A>", "<H:A>");
                msg = msg.replaceAll("</A>", "</H:A>");
                m_ctx.getRoom().sendMessage(set, "HISTORY", msg + "<H:clientID>" + clientID + "</H:clientID>" + "<H:userID>" + userID + "</H:userID>");
            }

            m_ctx.getRoom().sendMessage(set, "HISTORY", "<H:end></H:end>");
        } catch (Exception e) {
            s_log.error("HistoryRoomModule.onAddClient: JDBC doesn't work correct! PrepareStatement failed: " + e.getMessage() + " statement: " + statement + " upc msg: " + msg);
        } finally {
            try {
                if (statement != null) {
                    statement.close();
                }
                if (res != null) {
                    res.close();
                }
            } catch (Exception e) {
            }
        }
    }

    public void shutdown() {
        // clean up event listeners
        m_ctx.getRoom().removeEventListener(RoomEvent.MODULE_MESSAGE, this, "onModuleMessage");

        // clean up event listeners
        m_ctx.getRoom().removeEventListener(RoomEvent.ADD_CLIENT, this, "onAddClient");
    }

    private Integer getHistoryAttribute(Room room) {
        Attribute attr = null;
        Integer history = null;

        try {
            attr = room.getAttribute("_SHOW_HISTORY_OF_DAY");
        } catch (Exception e) {
        }
        if (attr != null) {
            s_log.debug("HistoryRoomModule: history param set: " + attr.getValue());
            history = Integer.valueOf((String) attr.getValue());
        } else {
            s_log.debug("HistoryRoomModule: history param not set");
        }

        if (history == null) {//set default value
            history = default_showHistory;

            String rID = room.getQualifiedID();

            if (exception_showHistory.containsKey(rID)) {
                history = exception_showHistory.get(rID);
            }
        }

        return history;
    }

    protected boolean connectToDatabase() {
        try {
            if (connect != null && !connect.isClosed()) {
                return true;
            } else if (connect != null) {
                s_log.error("HistoryRoomModule: Connection.isClosed return true!");
            }

            // This will load the MySQL driver, each DB has its own driver
            Class.forName("com.mysql.jdbc.Driver");
            // Setup the connection with the DB
            connect = DriverManager
                    .getConnection("jdbc:mysql://127.0.0.1:3306/unionserver?useUnicode=true&characterEncoding=UTF-8", databaseLogin, databasePass);

            if (connect == null) {
                s_log.error("HistoryRoomModule: JDBC doesn't work correct! Connection is nul!");
            } else {
                s_log.debug("HistoryRoomModule: JDBC - connected");
            }

//	  return true;
        } catch (ClassNotFoundException e) {
            s_log.error("HistoryRoomModule: JDBC doesn't work correct! ClassNotFound: " + e.getMessage());
            return false;
        } catch (SQLException e) {
            s_log.error("HistoryRoomModule: JDBC doesn't work correct! SQLException: " + e.getMessage());
            return false;
        } catch (Exception e) {
            s_log.error("HistoryRoomModule: JDBC doesn't work correct! Connection failed: " + e.getMessage());
            return false;
        } finally {
            //   closeDB();
        }

        if (connect == null) {
            return false;
        }

        return true;
    }

    // You need to close the resultSet
    protected void closeDB() {
        try {
            if (connect != null) {
                connect.close();
            }
        } catch (Exception e) {
            s_log.error("HistoryRoomModule: JDBC doesn't work correct! Close connection failed");
        }
    }
}
