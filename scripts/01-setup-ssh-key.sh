#!/bin/bash

# Setup SSH Key for Contract Documents Transfer
# This script helps you add the SSH public key to the dev server

echo "================================================================"
echo "  SSH Key Setup for Contract Documents Transfer"
echo "================================================================"
echo ""
echo "SSH public key has been generated. You need to add this key to the"
echo "dev server (10.55.0.51) to allow passwordless access."
echo ""
echo "PUBLIC KEY (copy this):"
echo "----------------------------------------------------------------"
cat ~/.ssh/id_ed25519.pub
echo "----------------------------------------------------------------"
echo ""
echo "INSTRUCTIONS:"
echo "1. SSH to the dev server: ssh dchevalier@10.55.0.51"
echo "2. Run: mkdir -p ~/.ssh && chmod 700 ~/.ssh"
echo "3. Run: nano ~/.ssh/authorized_keys"
echo "4. Paste the public key above on a new line"
echo "5. Save and exit (Ctrl+X, Y, Enter)"
echo "6. Run: chmod 600 ~/.ssh/authorized_keys"
echo "7. Exit the dev server"
echo ""
echo "Then test the connection by running:"
echo "  ssh dchevalier@10.55.0.51 'echo Connection successful'"
echo ""
echo "If that works without asking for a password, you're ready to proceed!"
echo "================================================================"
