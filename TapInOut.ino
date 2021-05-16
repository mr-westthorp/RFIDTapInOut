
/*************************************************************************
   This is for the ESP32 with built in OLED screen and RFID reader attached
  NB - Change C:\Users\Mr Westthorp\AppData\Local\Arduino15\packages\esp32\hardware\esp32\1.0.3\variants\lolin32\pins_arduino.h to the old copy when done!

  Wiring:

  ESP32-OLED    RFID-RC522
  3v3           3v3
  GND           GND
  GPIO 25       SDA //Swapped
  GPIO 26       RST //Swapped
  GPIO 14       CLK
  GPIO 13       MOSI
  GPIO 12       MISO

*/


#include <Arduino.h>
#include <Wire.h>
#include <WiFi.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <HTTPClient.h>
#include <ESPmDNS.h>
#include <WiFiUdp.h>
#include <ArduinoOTA.h>

#define USE_SERIAL Serial
#define SS_PIN 25 //Hard wired White / yellow 
#define RST_PIN 26 //
#define SCREEN_WIDTH 128 // OLED display width, in pixels
#define SCREEN_HEIGHT 64 // OLED display height, in pixels

MFRC522 mfrc522(SS_PIN, RST_PIN);   // Create MFRC522 instance.


// Declaration for an SSD1306 display connected to I2C (SDA, SCL pins)
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// Which way up? - 0 (Desk mount) or 2 (Wall mount)
int rotation = 0;
// How big do you like your text? 1-8
int text_size = 2;
String User_Key_Value = "";
// Scanner 1 - Sign In, 2 = Sign Out, 3 = Matron
String Scanner="8";


// A couple of contstants

const char* host = "http://enrichment.longridgetowers.com/scan_rfid/rfid.php?UID=";

String check_in = "http://enrichment.longridgetowers.com/scan_rfid/check_in.php?Scanner=" + Scanner + "&IP=";

const char* ssid = "ssid";
const char* password =  "password";

unsigned long previousMillis = 0;        // will store last time the WiFi was active

const long interval = 5 * 60000;           // WiFi connection interval for pinging (every 5 minutes)

void setup() {


  Wire.begin(5, 4);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C, false, false)) {
    // Address 0x3C for 128x64
    Serial.println(F("SSD1306 allocation failed"));
    for (;;);

    Serial.println("Ready!");
  }
  // Clear the buffer.
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(text_size);
  display.setCursor(0, 10);
  display.print("Checking  RFID");
  display.display();
  Serial.println("Setting up RFID...");
  SPI.begin();      // Initiate  SPI bus
  mfrc522.PCD_Init();   // Initiate MFRC522

  display.clearDisplay();
  display.setCursor(0, 10);
  display.print("Checking  WiFi");
  display.display();




 Serial.begin(115200);
  Serial.println("Booting");
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  while (WiFi.waitForConnectResult() != WL_CONNECTED) {
    Serial.println("Connection Failed! Rebooting...");
    delay(5000);
    ESP.restart();
  }

  // Port defaults to 3232
  // ArduinoOTA.setPort(3232);

  // Hostname defaults to esp3232-[MAC]
  ArduinoOTA.setHostname("RFID-8");

  // Password "iamgroot" hashed
  // ArduinoOTA.setPassword("plaintext");

  // Password can be set with it's md5 value as well
  // MD5(admin) = 21232f297a57a5a743894a0e4a801fc3
  ArduinoOTA.setPasswordHash("473c0812623754d187d1e4c96af5d5cb");

  ArduinoOTA
    .onStart([]() {
      String type;
      if (ArduinoOTA.getCommand() == U_FLASH)
        type = "sketch";
      else // U_SPIFFS
        type = "filesystem";

      // NOTE: if updating SPIFFS this would be the place to unmount SPIFFS using SPIFFS.end()
      Serial.println("Start updating " + type);
    })
    .onEnd([]() {
      Serial.println("\nEnd");
    })
    .onProgress([](unsigned int progress, unsigned int total) {
      Serial.printf("Progress: %u%%\r", (progress / (total / 100)));
    })
    .onError([](ota_error_t error) {
      Serial.printf("Error[%u]: ", error);
      if (error == OTA_AUTH_ERROR) Serial.println("Auth Failed");
      else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
      else if (error == OTA_CONNECT_ERROR) Serial.println("Connect Failed");
      else if (error == OTA_RECEIVE_ERROR) Serial.println("Receive Failed");
      else if (error == OTA_END_ERROR) Serial.println("End Failed");
    });

  ArduinoOTA.begin();

  Serial.println("Ready");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
  /*
  Wire.begin(5, 4);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C, false, false)) {
    // Address 0x3C for 128x64
    Serial.println(F("SSD1306 allocation failed"));
    for (;;);

    Serial.println("Ready!");
  }
  // Clear the buffer.
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(text_size);
  display.setCursor(0, 10);
  display.print("Checking  RFID");
  display.display();
  Serial.println("Setting up RFID...");
  SPI.begin();      // Initiate  SPI bus
  mfrc522.PCD_Init();   // Initiate MFRC522

  display.clearDisplay();
  display.setCursor(0, 10);
  display.print("Checking  WiFi");
  display.display();
*/

  
  Serial.println(WiFi.localIP());
  check_in = check_in + WiFi.localIP().toString();
  Serial.println("Address = " + check_in);

  Serial.println("Setup Complete");
  Serial.println("Please Scan Your Card");
}

int value = 0;

void loop() {

  ArduinoOTA.handle();

  unsigned long currentMillis = millis();

  if (currentMillis - previousMillis >= interval) {
    // save the last time you blinked the LED
    previousMillis = currentMillis;
    check_in_to_keep_WiFi(check_in);

  }

  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(text_size);
  display.setCursor(0, 10);
  display.print("Please tapyour tag");

  display.display();





  // Look for new cards
  if ( ! mfrc522.PICC_IsNewCardPresent())
  {
    return;
  }
  // Select one of the cards
  if ( ! mfrc522.PICC_ReadCardSerial())
  {
    return;
  }
  //Show UID on serial monitor
  Serial.println();
  Serial.print(" UID tag :");
  String content = "";
  byte letter;
  for (byte i = 0; i < mfrc522.uid.size; i++)
  {
    Serial.print(mfrc522.uid.uidByte[i] < 0x10 ? " 0" : " ");
    Serial.print(mfrc522.uid.uidByte[i], HEX);
    content.concat(String(mfrc522.uid.uidByte[i] < 0x10 ? " 0" : " "));
    content.concat(String(mfrc522.uid.uidByte[i], HEX));
  }
  content.toUpperCase();
  Serial.println();
  // User_Key_Value = content.substring(1);
  // Remove any white spaces..
  User_Key_Value = content;
  User_Key_Value.replace(" ", "");

  Serial.println(User_Key_Value);

  //previousMillis = currentMillis;
  check_with_server();
}

void check_in_to_keep_WiFi(String request)
{
  if ((WiFi.status() == WL_CONNECTED)) { //Check the current connection status

    HTTPClient http;

    http.begin(request); // Send the message

    int httpCode = http.GET();

    if (httpCode > 0) { //Check for the returning code

      Serial.println(httpCode);
    }

    else {
      Serial.println(httpCode);
      Serial.println("Error on HTTP request");
    }

    http.end(); //Free the resources
  }
}
void check_with_server() {

  String request = String(host) + String(User_Key_Value) + "&Scanner=" + String(Scanner);
  Serial.println(request);

  if ((WiFi.status() == WL_CONNECTED)) { //Check the current connection status

    HTTPClient http;

    http.begin(request); // Send the message

    int httpCode = http.GET();

    if (httpCode > 0) { //Check for the returning code

      String payload = http.getString();
      Serial.println(httpCode);
      Serial.println(payload);
      What_shall_we_do_with_the(payload);
    }

    else {
      Serial.println(httpCode);
      Serial.println("Error on HTTP request");
    }

    http.end(); //Free the resources
  }
}
/*
  // This will send the request to the server
  client.print(String("GET ") + request + " HTTP/1.1\r\n" +
             "Host: " + host + "\r\n" +
             "Connection: close\r\n\r\n");
  unsigned long timeout = millis();
  while (client.available() == 0) {
  if (millis() - timeout > 5000) {
    Serial.println(">>> Client Timeout !");
    client.stop();
    return;
  }
  }

  // Read all the lines of the reply from server and print them to Serial
  while (client.available()) {
  String payload = client.readStringUntil('\r');
  Serial.print(payload);
  What_shall_we_do_with_the(payload);
  }
  }
*/

void What_shall_we_do_with_the(String payload)
{

  /* So the payload can be anything,

      At the moment if the RFID has not been recognised, then you get:

      typically it could take the JSON form:

      {"Pupil":"Lucy Westthorp", "Scanned at ":"2020/01/30 10:06:55"}

      We could then parse this first string

  */

  display.clearDisplay();
  display.setTextSize(text_size);

  display.setTextColor(WHITE);
  display.setCursor(0, 10);

  if (payload.indexOf("Invalid") > 0) {

    display.print("Card Invalid");

  }
  else
  {

    // Tidy up the payload

    //display.println("Thank You: ");
    display.println(payload);
    display.display();
    Serial.print(payload);
    delay(2000);
  }
}
