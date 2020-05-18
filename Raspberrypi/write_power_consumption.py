#!/usr/bin/env python

# just some bitbang code for testing all 8 channels
import math
import RPi.GPIO as GPIO
import urllib2
import time
import os

import csv
import time


interval = 5 # 20 sec
DEBUG = 1
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)


output_file = "dataset4.csv" 

file_exists = os.path.isfile(output_file)
if not file_exists:
  with open(output_file, "w") as f:
      f.write("DeviceID, KilowattHours, TStamp")
      f.write("\r\n")

def getserial():
  # Extract serial from cpuinfo file
  cpuserial = "0000000000000000"
  try:
    f = open('/proc/cpuinfo','r')
    for line in f:
      if line[0:6]=='Serial':
        cpuserial = line[10:26]
    f.close()
  except:
    cpuserial = "ERROR000000000"
  return cpuserial
    

# this function is not used, its for future reference!
def slowspiwrite(clockpin, datapin, byteout):
  GPIO.setup(clockpin, GPIO.OUT)
  GPIO.setup(datapin, GPIO.OUT)
  for i in range(8):
    if (byteout & 0x80):
     GPIO.output(datapin, True)
    else:
     GPIO.output(datapin, False)
    byteout <<= 1
    GPIO.output(clockpin, True)
    GPIO.output(clockpin, False)

# this function is not used, its for future reference!
def slowspiread(clockpin, datapin):
  GPIO.setup(clockpin, GPIO.OUT)
  GPIO.setup(datapin, GPIO.IN)
  byteout = 0
  for i in range(8):
    GPIO.output(clockpin, False)
    GPIO.output(clockpin, True)
    byteout <<= 1
    if (GPIO.input(datapin)):
     byteout = byteout | 0x1
  return byteout

# read SPI data from MCP3008 chip, 8 possible adc's (0 thru 7)
def readadc(adcnum, clockpin, mosipin, misopin, cspin):
  if ((adcnum > 7) or (adcnum < 0)):
    return -1
  GPIO.output(cspin, True)

  GPIO.output(clockpin, False)  # start clock low
  GPIO.output(cspin, False)     # bring CS low

  commandout = adcnum
  commandout |= 0x18  # start bit + single-ended bit
  commandout <<= 3    # we only need to send 5 bits here
  for i in range(5):
    if (commandout & 0x80):
     GPIO.output(mosipin, True)
    else:
        GPIO.output(mosipin, False)
                  commandout <<= 1
                  GPIO.output(clockpin, True)
                  GPIO.output(clockpin, False)

  adcout = 0
  # read in one empty bit, one null bit and 10 ADC bits
  for i in range(12):
    GPIO.output(clockpin, True)
    GPIO.output(clockpin, False)
    adcout <<= 1
    if (GPIO.input(misopin)):
     adcout |= 0x1

  GPIO.output(cspin, True)

  adcout /= 2       # first bit is 'null' so drop it
  return adcout

  # change these as desired
  SPICLK = 18
  SPIMISO = 23
  SPIMOSI = 24
  SPICS = 25

  # set up the SPI interface pins
  GPIO.setup(SPIMOSI, GPIO.OUT)
  GPIO.setup(SPIMISO, GPIO.IN)
  GPIO.setup(SPICLK, GPIO.OUT)
  GPIO.setup(SPICS, GPIO.OUT)

# Note that bitbanging SPI is incredibly slow on the Pi as its not
# a RTOS - reading the ADC takes about 30 ms (~30 samples per second)
# which is awful for a microcontroller but better-than-nothing for Linux

#RMS calcule fonction
def CalcIrms(cts):
    NUMBER_OF_SAMPLES = 1000
    SUPPLYVOLTAGE = 3300
    ICAL = 111.1
    sumI = 0
    sampleI = 512
    filteredI = 0
    for n in range (0, NUMBER_OF_SAMPLES):
       
        lastSampleI = sampleI
        sampleI = readadc(cts, SPICLK, SPIMOSI, SPIMISO, SPICS)
        #print sampleI
        lastFilteredI = filteredI
        filteredI = 0.996*(lastFilteredI+sampleI-lastSampleI)
        sqI = filteredI * filteredI
        sumI += sqI
       
    I_RATIO = ICAL * ((SUPPLYVOLTAGE/1000.0) / 1023.0)
    Irms = I_RATIO * math.sqrt(sumI / NUMBER_OF_SAMPLES)
    sumI = 0
   
    return Irms


# mcp3008 input
ct1_adc = 0

# Convert the reading value in RMS

while True:
    ct1 = CalcIrms(ct1_adc)*120
    with open(output_file, 'a') as myFile:
      writer = csv.writer(myFile,lineterminator='\r\n')
      ts = time.localtime()
      myData = [[4, str(ct1/1000), time.strftime("%Y-%m-%d %H:%M", ts)]]
          writer.writerows(myData)
    time.sleep(5)
