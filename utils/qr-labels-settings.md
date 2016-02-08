# Settings for QR labels generator

* *Rows*: 11
* *Columns*: 8
* *QR code*: 160px

```css
body {
  margin: 0;
  padding: 0;
}

p {
  margin: 0;
  padding: 0;
}

img {
  height: 19mm;
  margin: 0 2mm 5mm;
  width: 19mm;
}

table {
  border: medium none;
  border-collapse: collapse;
  height: 200mm;
  width: 275mm;
}

td {
  border: medium none;
  margin: 0;
  padding: 0;
  text-align: center;
}

.sku {
  font-size: 2mm;
  margin-top: -7mm;
  max-width: 19mm;
  overflow: hidden;
  padding-right: 5mm;
  position: absolute;
  text-align: right;
}

@page {
    margin: 0;
}

```
