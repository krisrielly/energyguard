import json
import os
import sys
token_data = sys.argv[1]

token_data = json.loads(token_data)
print token_data["access_token"]

sys.exit(0)

