# ImageMagick Security Configuration

This document describes the ImageMagick security policy that should be implemented on the server to prevent exploitation vulnerabilities.

## Security Policy File

Create or update: `/etc/ImageMagick-6/policy.xml` (or `/etc/ImageMagick-7/policy.xml` depending on version)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE policymap [
<!ELEMENT policymap (policy)+>
<!ELEMENT policy (#PCDATA)>
<!ATTLIST policy domain (delegate|coder|filter|path|resource) #IMPLIED>
<!ATTLIST policy name CDATA #IMPLIED>
<!ATTLIST policy rights CDATA #IMPLIED>
<!ATTLIST policy pattern CDATA #IMPLIED>
<!ATTLIST policy value CDATA #IMPLIED>
]>
<policymap>
  <!-- Disable vulnerable coders -->
  <policy domain="coder" rights="none" pattern="EPHEMERAL" />
  <policy domain="coder" rights="none" pattern="URL" />
  <policy domain="coder" rights="none" pattern="HTTPS" />
  <policy domain="coder" rights="none" pattern="MVG" />
  <policy domain="coder" rights="none" pattern="MSL" />
  <policy domain="coder" rights="none" pattern="TEXT" />
  <policy domain="coder" rights="none" pattern="SHOW" />
  <policy domain="coder" rights="none" pattern="WIN" />
  <policy domain="coder" rights="none" pattern="PLT" />

  <!-- Allow only necessary formats -->
  <policy domain="coder" rights="read|write" pattern="TIFF" />
  <policy domain="coder" rights="read|write" pattern="TIF" />
  <policy domain="coder" rights="read|write" pattern="PNG" />
  <policy domain="coder" rights="read|write" pattern="JPEG" />
  <policy domain="coder" rights="read|write" pattern="JPG" />
  <policy domain="coder" rights="read" pattern="PDF" />

  <!-- Resource limits to prevent DoS -->
  <policy domain="resource" name="memory" value="256MiB"/>
  <policy domain="resource" name="map" value="512MiB"/>
  <policy domain="resource" name="width" value="16KP"/>
  <policy domain="resource" name="height" value="16KP"/>
  <policy domain="resource" name="area" value="128MB"/>
  <policy domain="resource" name="disk" value="1GiB"/>
  <policy domain="resource" name="time" value="120"/>

  <!-- Disable ghostscript delegates for PDF (use direct PDF coder only) -->
  <policy domain="delegate" rights="none" pattern="gs" />
</policymap>
```

## Installation Instructions

### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install imagemagick
sudo nano /etc/ImageMagick-6/policy.xml
# Paste the policy above, save and exit
sudo systemctl restart apache2  # or your web server
```

### CentOS/RHEL:
```bash
sudo yum install ImageMagick
sudo nano /etc/ImageMagick-7/policy.xml
# Paste the policy above, save and exit
sudo systemctl restart httpd
```

## Verification

Test that the policy is applied:
```bash
php -r "echo Imagick::getVersion()['versionString'];"
```

Test TIFF conversion (should work):
```bash
convert test.tif test.png
```

## References
- [ImageMagick Security Policy](https://imagemagick.org/script/security-policy.php)
- [CVE-2016-3714 (ImageTragick)](https://imagetragick.com/)
