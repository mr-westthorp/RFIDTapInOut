# RFIDTapInOut

Here is some simple code for an ESP32-OLED + MFRC522 RFID scanner.

The ESP32-OLED requires a special pinout so you will need to swap your pins.h file. I have incldued both the original and modified versions here.

We use this at our school to log pupils going to music lessons, vistiing the school matron and arriving or leaving the school site.

Principle of opertion:

1. Pupil scans their tag
2. ESP recovers the UUID from the tag and calls a PHp file on the webserver.
3. The PHP file responds and sends the name of the pupil to the ESP for confirmation or a message saying "unknown"
4. The PHO file also records the tap in a MYSQL database.


