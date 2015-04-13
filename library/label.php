<?php
namespace MapFile;

class Label {
  private $color = array(0, 0, 0);
  private $outlinecolor;

  public $align;
  public $font;
  public $maxscaledenom;
  public $minscaledenom;
  public $position;
  public $size = self::SIZE_MEDIUM;
  public $type = self::TYPE_BITMAP;

  const ALIGN_LEFT = 0;
  const ALIGN_CENTER = 1;
  const ALIGN_RIGHT = 2;

  const POSITION_UL = 101;
  const POSITION_LR = 102;
  const POSITION_UR = 103;
  const POSITION_LL = 104;
  const POSITION_CR = 105;
  const POSITION_CL = 106;
  const POSITION_UC = 107;
  const POSITION_LC = 108;
  const POSITION_CC = 109;
  const POSITION_XY = 111;
  const POSITION_AUTO = 110;
  const POSITION_AUTO2 = 114;
  const POSITION_FOLLOW = 112;
  const POSITION_NONE = 113;

  const SIZE_TINY = 0;
  const SIZE_SMALL = 1;
  const SIZE_MEDIUM = 2;
  const SIZE_LARGE = 3;
  const SIZE_GIANT = 4;

  const TYPE_TRUETYPE = 0;
  const TYPE_BITMAP = 1;

  public function __construct($label = NULL) {
    if (!is_null($label)) $this->read($label);
  }

  public function setColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255)
      $this->color = array($r,$g,$b);
    else
      throw new Exception('Invalid LABEL COLOR('.$r.' '.$g.' '.$b.').');
  }
  public function setOutlineColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255)
      $this->outlinecolor = array($r,$g,$b);
    else
      throw new Exception('Invalid LABEL OUTLINECOLOR('.$r.' '.$g.' '.$b.').');
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

  public function write($indent = 0) {
    $label  = str_repeat(' ', $indent*2).'LABEL'.PHP_EOL;
    $label .= str_repeat(' ', $indent*2).'  TYPE '.$this->convertType().PHP_EOL;
    if ($this->type == self::TYPE_TRUETYPE && !empty($this->font)) $label .= str_repeat(' ', $indent*2).'  FONT "'.$this->font.'"'.PHP_EOL;
    if ($this->type == self::TYPE_BITMAP) $label .= str_repeat(' ', $indent*2).'  SIZE '.$this->convertSize().PHP_EOL;
    else if ($this->type == self::TYPE_TRUETYPE) $label .= str_repeat(' ', $indent*2).'  SIZE '.floatval($this->size).PHP_EOL;
    if (!is_null($this->align) && strlen($this->align) > 0) $label .= str_repeat(' ', $indent*2).'  ALIGN '.$this->convertAlign().PHP_EOL;
    if (!is_null($this->position) && strlen($this->position) > 0) $label .= str_repeat(' ', $indent*2).'  POSITION '.$this->convertPosition().PHP_EOL;
    if (!is_null($this->minscaledenom)) $label .= '        MINSCALEDENOM '.floatval($this->minscaledenom).PHP_EOL;
    if (!is_null($this->maxscaledenom)) $label .= '        MAXSCALEDENOM '.floatval($this->maxscaledenom).PHP_EOL;
    if (!empty($this->color) && count($this->color) == 3 && array_sum($this->color) >= 0) $label .= str_repeat(' ', $indent*2).'  COLOR '.implode(' ',$this->color).PHP_EOL;
    if (!empty($this->outlinecolor) && count($this->outlinecolor) == 3 && array_sum($this->outlinecolor) >= 0) $label .= str_repeat(' ', $indent*2).'  OUTLINECOLOR '.implode(' ',$this->outlinecolor).PHP_EOL;
    $label .= str_repeat(' ', $indent*2).'END # LABEL'.PHP_EOL;

    return $label;
  }

  private function read($array) {
    $label = FALSE;

    foreach ($array as $_sz) {
      $sz = trim($_sz);

      if (preg_match('/^LABEL$/i', $sz)) $label = TRUE;
      else if ($label && preg_match('/^END( # LABEL)?$/i', $sz)) $label = FALSE;

      else if ($label && preg_match('/^TYPE (.+)$/i', $sz, $matches)) $this->type = self::convertType($matches[1]);
      else if ($label && preg_match('/^FONT "(.+)"$/i', $sz, $matches)) $this->font = $matches[1];
      else if ($label && preg_match('/^SIZE ([0-9]+)$/i', $sz, $matches)) $this->size = $matches[1];
      else if ($label && preg_match('/^SIZE (.+)$/i', $sz, $matches)) $this->size = self::convertSize($matches[1]);
      else if ($label && preg_match('/^ALIGN (.+)$/i', $sz, $matches)) $this->align = self::convertAlign($matches[1]);
      else if ($label && preg_match('/^COLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) $this->color = array($matches[1], $matches[2], $matches[3]);
      else if ($label && preg_match('/^OUTLINECOLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) $this->outlinecolor = array($matches[1], $matches[2], $matches[3]);
      else if ($label && preg_match('/^MINSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->minscaledenom = $matches[1];
      else if ($label && preg_match('/^MAXSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->maxscaledenom = $matches[1];
    }
  }

  private function convertAlign($a = NULL) {
    $aligns = array(
      self::POSITION_UL     => 'UL',
      self::POSITION_LR     => 'LR',
      self::POSITION_UR     => 'UR',
      self::POSITION_LL     => 'LL',
      self::POSITION_CR     => 'CR',
      self::POSITION_CL     => 'CL',
      self::POSITION_UC     => 'UC',
      self::POSITION_LC     => 'LC',
      self::POSITION_CC     => 'CC',
      self::POSITION_XY     => 'XY',
      self::POSITION_AUTO   => 'AUTO',
      self::POSITION_AUTO2  => 'AUTO2',
      self::POSITION_FOLLOW => 'FOLLOW',
      self::POSITION_NONE   => 'NONE'
    );

    if (is_null($a)) return $aligns[$this->align];
    else if (is_numeric($a)) return (isset($aligns[$a]) ? $aligns[$a] : FALSE);
    else return array_search($a, $aligns);
  }
  private function convertPosition($p = NULL) {
    $positions = array(
      self::ALIGN_LEFT   => 'LEFT',
      self::ALIGN_CENTER => 'CENTER',
      self::ALIGN_RIGHT  => 'RIGHT'
    );

    if (is_null($p)) return $positions[$this->align];
    else if (is_numeric($p)) return (isset($positions[$p]) ? $positions[$p] : FALSE);
    else return array_search($p, $positions);
  }
  private function convertSize($s = NULL) {
    $sizes = array(
      self::SIZE_TINY   => 'TINY',
      self::SIZE_SMALL  => 'SMALL',
      self::SIZE_MEDIUM => 'MEDIUM',
      self::SIZE_LARGE  => 'LARGE',
      self::SIZE_GIANT  => 'GIANT',
    );

    if (is_null($s)) return $sizes[$this->size];
    else if (is_numeric($s)) return (isset($sizes[$s]) ? $sizes[$s] : FALSE);
    else return array_search($s, $sizes);
  }
  private function convertType($t = NULL) {
    $types = array(
      self::TYPE_TRUETYPE => 'TRUETYPE',
      self::TYPE_BITMAP   => 'BITMAP'
    );

    if (is_null($t)) return $types[$this->type];
    else if (is_numeric($t)) return (isset($types[$t]) ? $types[$t] : FALSE);
    else return array_search($t, $types);
  }
}