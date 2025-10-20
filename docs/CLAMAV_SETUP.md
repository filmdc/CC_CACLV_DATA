# ClamAV Malware Scanning Setup

Optional: Enable malware scanning for uploaded check images.

## Installation

### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install clamav clamav-daemon
sudo systemctl enable clamav-daemon
sudo systemctl start clamav-daemon
sudo freshclam  # Update virus definitions
```

### CentOS/RHEL:
```bash
sudo yum install clamav clamav-scanner clamav-scanner-systemd
sudo systemctl enable clamd@scan
sudo systemctl start clamd@scan
sudo freshclam
```

## Configuration

Enable in `.env`:
```bash
ENABLE_MALWARE_SCANNING=true
```

## Testing

Test that ClamAV is working:
```bash
clamdscan --version
clamdscan /tmp/test_file.txt
```

## Automatic Updates

ClamAV should automatically update virus definitions. Verify:
```bash
sudo systemctl status clamav-freshclam
```
