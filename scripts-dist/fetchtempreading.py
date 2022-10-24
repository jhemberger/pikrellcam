#!/usr/bin/python3
import sys
import subprocess
import datetime
import sqlite3
import os
import SDL_Pi_HDC1080

hdc1080 = SDL_Pi_HDC1080.SDL_Pi_HDC1080()

def store_in_db(ts: str, temp: float, rh: float):
    path: str = os.path.abspath(os.path.join(os.path.dirname(__file__), os.path.pardir)) + "/www/data.db"
    with sqlite3.connect(path) as conn:
        c = conn.cursor()
        c.execute("CREATE TABLE IF NOT EXISTS hdc1080 (Timestamp text, Temperature float, Humidity float);")
        query = str.format("INSERT INTO hdc1080 (Timestamp, Temperature, Humidity) VALUES ('%s', %s, %s)" % (ts, temp, rh))
        c.execute(query)
        conn.commit()


def main():
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    temperature = hdc1080.readTemperature()
    humidity = hdc1080.readHumidity()
    # print("Timestamp = " + timestamp)
    # print("Temperature = %3.1f C" % temperature)
    # print("Humidity = %3.1f %%" % humidity)
    store_in_db(timestamp, temperature, humidity)


if __name__ == "__main__":
    main()
