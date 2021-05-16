# RFIDTapInOut

Here is some simple code for an ESP32-OLED + MFRC522 RFID scanner.

The ESP32-OLED requires a special pinout so you will need to swap your pins.h file. I have incldued both the original and modified versions here.

We use this at our school to log pupils going to music lessons, vistiing the school matron and arriving or leaving the school site.

Principle of opertion:

1. Pupil scans their tag
2. ESP recovers the UUID from the tag and calls a PHp file on the webserver.
3. The PHP file responds and sends the name of the pupil to the ESP for confirmation or a message saying "unknown"
4. The PHP file also records the tap in a MYSQL database.

A 3d printed box is available via OnShape at https://cad.onshape.com/documents/60977efe1a047750668d3222/w/153b82bc0decbbb8fbda3180/e/c881baae505830e882e0e457


