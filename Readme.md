
# DoubleMetaphone

![Coverage](https://raw.githubusercontent.com/AGWD/badges/master/coverage_dblmetaphone.svg)

Encode a string into a phonetic one with the Double Metaphone algorithm.

```php
$encoder = new DoubleMetaphone();

//primary
echo $encoder->doubleMetaphone('Brodowski', true) . ' = ';
echo $encoder->doubleMetaphone('Bratowski', true) . PHP_EOL;
// >> PRTF = PRTF

//secondary
echo $encoder->doubleMetaphone('Brodowski', false) . ' = ';
echo $encoder->doubleMetaphone('Bratowski', false) . PHP_EOL;
// >> PRTS = PRTS

//using a longer metaphone length
$encoder->setMaxCodeLen(8);
//primary
echo $encoder->doubleMetaphone('Brodowski', true) . ' = ';
echo $encoder->doubleMetaphone('Bratowski', true) . PHP_EOL;
// >> PRTFSK = PRTFSK

//secondary
echo $encoder->doubleMetaphone('Brodowskiwitz', false) . ' = ';
echo $encoder->doubleMetaphone('Bratowskiwits', false) . PHP_EOL;
// >> PRTSKTS = PRTSKTS

//using a custom max code lenth and getting instance of DoubleMetaphoneResult and its toJson result
$encoder->setMaxCodeLen(6);

echo $encoder->getDoubleMetaphoneResult('Eckhardt')->toJson() . PHP_EOL;
// >> {"value":"ECKHARDT","primary":"AKRT","alternate":"AKRT"}

echo $encoder->getDoubleMetaphoneResult('Wolfeschlegelsteinhausenbergerdorff')->toJson() . PHP_EOL;
// >> {"value":"WOLFESCHLEGELSTEINHAUSENBERGERDORFF","primary":"ALFXLJ","alternate":"FLFXLK"}

```

## Installation

Composer - 
todo

## Information

* compatible with [Apache Jakarta Commons Codec](http://commons.apache.org/codec/) class DoubleMetaphone, Version 1.5 - 1.8
* maximum length of codec settable (default=4)
* implements the standard (=primary) and alternate encoding

## Tests

## Credits

This Implementation is based on the algorithm by <CITE>Lawrence Philips</CITE>.

* Original Article: [http://www.cuj.com/documents/s=8038/cuj0006philips/](http://www.cuj.com/documents/s=8038/cuj0006philips/)
* Original Source Code: [ftp://ftp.cuj.com/pub/2000/1806/philips.zip](ftp://ftp.cuj.com/pub/2000/1806/philips.zip)

This PHP implementation is a 1:1 port from [Apache Jakarta Commons Codec](http://commons.apache.org/codec/)
class <tt>DoubleMetaphone</tt>.

The Java library is licensed under [Apache 2.0](http://www.apache.org/licenses/LICENSE-2.0) which is the reason
why this has the same license.

## License

Copyright 2021 Adrian Green

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
