<?php
namespace MapFile;

class Style {
  private $color;
  private $outlinecolor;

  public $angle;
  public $maxscaledenom;
  public $minscaledenom;
  public $opacity;
  public $outlinewidth;
  public $pattern;
  public $size;
  public $symbolname;
  public $width;

  public function __construct($style = NULL) {
    if (!is_null($style)) $this->read($style);
  }

  public function setColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255)
      $this->color = array($r,$g,$b);
    else
      throw new Exception('Invalid STYLE COLOR('.$r.' '.$g.' '.$b.').');
  }
  public function setOutlineColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255)
      $this->outlinecolor = array($r,$g,$b);
    else
      throw new Exception('Invalid STYLE OUTLINECOLOR('.$r.' '.$g.' '.$b.').');
  }

  public function getColor() {
    return array('r' => $this->color[0], 'g' => $this->color[1], 'b' => $this->color[2]);
  }
  public function getOutlineColor() {
    return array('r' => $this->outlinecolor[0], 'g' => $this->outlinecolor[1], 'b' => $this->outlinecolor[2]);
  }

  public function unsetColor() {
    $this->color = NULL;
  }
  public function unsetOutlineColor() {
    $this->outlinecolor = NULL;
  }

  public function write() {
    $style  = '      STYLE'.PHP_EOL;
    if (!is_null($this->angle)) $style .= '        ANGLE '.floatval($this->angle).PHP_EOL;
    if (!empty($this->color) && count($this->color) == 3 && array_sum($this->color) >= 0) $style .= '        COLOR '.implode(' ',$this->color).PHP_EOL;
    if (!is_null($this->maxscaledenom)) $style .= '        MAXSCALEDENOM '.floatval($this->maxscaledenom).PHP_EOL;
    if (!is_null($this->minscaledenom)) $style .= '        MINSCALEDENOM '.floatval($this->minscaledenom).PHP_EOL;
    if (!is_null($this->opacity)) $style .= '        OPACITY '.intval($this->opacity).PHP_EOL;
    if (!empty($this->outlinecolor) && count($this->outlinecolor) == 3 && array_sum($this->outlinecolor) >= 0) $style .= '        OUTLINECOLOR '.implode(' ',$this->outlinecolor).PHP_EOL;
    if (!is_null($this->outlinewidth)) $style .= '        OUTLINEWIDTH '.floatval($this->outlinewidth).PHP_EOL;
    if (!is_null($this->size)) $style .= '        SIZE '.floatval($this->size).PHP_EOL;
    if (!is_null($this->width)) $style .= '        WIDTH '.floatval($this->width).PHP_EOL;
    if (!empty($this->symbolname)) $style .= '        SYMBOL "'.$this->symbolname.'"'.PHP_EOL;
    $style .= '      END # STYLE'.PHP_EOL;
    return $style;
  }

  private function read($array) {
    $style = FALSE;

    foreach ($array as $_sz) {
      $sz = trim($_sz);

      if (preg_match('/^STYLE$/i', $sz)) $style = TRUE;
      else if ($style && preg_match('/^END( # STYLE)?$/i', $sz)) $style = FALSE;

      else if ($style && preg_match('/^ANGLE ([0-9\.]+)$/i', $sz, $matches)) $this->angle = $matches[1];
      else if ($style && preg_match('/^COLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) $this->color = array($matches[1], $matches[2], $matches[3]);
      else if ($style && preg_match('/^MAXSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->maxscaledenom = $matches[1];
      else if ($style && preg_match('/^MINSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->minscaledenom = $matches[1];
      else if ($style && preg_match('/^OPACITY ([0-9]+)$/i', $sz, $matches)) $this->opacity = $matches[1];
      else if ($style && preg_match('/^OUTLINECOLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) $this->outlinecolor = array($matches[1], $matches[2], $matches[3]);
      else if ($style && preg_match('/^OUTLINEWIDTH ([0-9\.]+)$/i', $sz, $matches)) $this->outlinewidth = $matches[1];
      else if ($style && preg_match('/^SIZE ([0-9\.]+)$/i', $sz, $matches)) $this->size = $matches[1];
      else if ($style && preg_match('/^SYMBOL "(.+)"$/i', $sz, $matches)) $this->symbolname = $matches[1];
      else if ($style && preg_match('/^WIDTH ([0-9\.]+)$/i', $sz, $matches)) $this->width = $matches[1];
    }
  }
}
