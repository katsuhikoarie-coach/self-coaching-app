#!/bin/bash
set -e
cd "$(dirname "$0")"
python3 -m venv venv
venv/bin/pip install -r requirements.txt
venv/bin/python -m uvicorn main:app --host 0.0.0.0 --port $PORT
