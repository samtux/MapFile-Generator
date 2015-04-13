<?php
namespace MapFile;

require_once('class.php');

class Layer {
  private $metadata = array();

  private $_classes = array();

  public $connection;
  public $connectiontype = self::CONNECTIONTYPE_LOCAL;
  public $classitem;
  public $data;
  public $filter;
  public $filteritem;
  public $group;
  public $labelitem;
  public $maxscaledenom;
  public $minscaledenom;
  public $name;
  public $opacity;
  public $projection;
  public $status = self::STATUS_OFF;
  public $tileitem = 'location';
  public $tolerance;
  public $tolereanceunits = self::UNITS_PIXELS;
  public $type = self::TYPE_POINT;
  public $units;

  const CONNECTIONTYPE_CONTOUR = 0;
  const CONNECTIONTYPE_LOCAL = 1;
  const CONNECTIONTYPE_OGR = 2;
  const CONNECTIONTYPE_ORACLESPATIAL = 3;
  const CONNECTIONTYPE_PLUGIN = 4;
  const CONNECTIONTYPE_POSTGIS = 5;
  const CONNECTIONTYPE_SDE = 6;
  const CONNECTIONTYPE_UNION = 7;
  const CONNECTIONTYPE_UVRASTER = 8;
  const CONNECTIONTYPE_WFS = 9;
  const CONNECTIONTYPE_WMS = 10;

  const STATUS_ON = 1;
  const STATUS_OFF = 0;

  const TYPE_POINT = 0;
  const TYPE_LINE = 1;
  const TYPE_POLYGON = 2;
  const TYPE_RASTER = 3;
  const TYPE_QUERY = 5;
  const TYPE_CIRCLE = 6;
  const TYPE_TILEINDEX = 7;
  const TYPE_CHART = 8;

  const UNITS_INCHES = 0;
  const UNITS_FEET = 1;
  const UNITS_MILES = 2;
  const UNITS_METERS = 3;
  const UNITS_KILOMETERS = 4;
  const UNITS_DD = 5;
  const UNITS_PIXELS = 6;
  const UNITS_NAUTICALMILES = 8;

  public function __construct($layer = NULL) {
    if (!is_null($layer)) $this->read($layer);
  }

  public function setMetadata($key, $value) {
    $this->metadata[$key] = $value;
  }

  public function getClasses() {
    return $this->_classes;
  }
  public function getClass($i) {
    return (isset($this->_classes[$i]) ? $this->_classes[$i] : FALSE);
  }
  public function getMetadata($key) {
    return (isset($this->metadata[$key]) ? $this->metadata[$key] : FALSE);
  }

  public function removeMetadata($key) {
    if (isset($this->metadata[$key])) unset($this->metadata[$key]);
  }

  public function addClass($class = NULL) {
    if (is_null($class)) $class = new LayerClass();
    $count = array_push($this->_classes, $class);
    return $this->_classes[$count-1];
  }

  public function write() {
    $layer  = '  LAYER'.PHP_EOL;
    $layer .= '    STATUS '.$this->convertStatus().PHP_EOL;
    if (!empty($this->group)) $layer .= '    GROUP "'.$this->group.'"'.PHP_EOL;
    if (!empty($this->name)) $layer .= '    NAME "'.$this->name.'"'.PHP_EOL;
    $layer .= '    TYPE '.$this->convertType().PHP_EOL;
    if (!empty($this->units)) $layer .= '    UNITS '.$this->convertUnits().PHP_EOL;
    if (!empty($this->connectiontype) && $this->connectiontype != self::CONNECTIONTYPE_LOCAL && !empty($this->connection)) {
      $layer .= '    CONNECTIONTYPE '.$this->convertConnectiontype().PHP_EOL;
      $layer .= '    CONNECTION "'.$this->connection.'"'.PHP_EOL;
    }
    if (!empty($this->data)) $layer .= '    DATA "'.$this->data.'"'.PHP_EOL;
    if (!empty($this->filteritem)) $layer .= '    FILTERITEM "'.$this->filteritem.'"'.PHP_EOL;
    if (!empty($this->filter)) $layer .= '    FILTER "'.$this->filter.'"'.PHP_EOL;
    if (!empty($this->projection)) {
      $layer .= '    PROJECTION'.PHP_EOL;
      $layer .= '      "init='.strtolower($this->projection).'"'.PHP_EOL;
      $layer .= '    END # PROJECTION'.PHP_EOL;
    }
    if (!empty($this->metadata)) {
      $layer .= '    METADATA'.PHP_EOL;
      foreach ($this->metadata as $k => $v) $layer .= '      "'.$k.'" "'.$v.'"'.PHP_EOL;
      $layer .= '    END # METADATA'.PHP_EOL;
    }
    if (!is_null($this->minscaledenom)) $layer .= '    MINSCALEDENOM '.floatval($this->minscaledenom).PHP_EOL;
    if (!is_null($this->maxscaledenom)) $layer .= '    MAXSCALEDENOM '.floatval($this->maxscaledenom).PHP_EOL;
    if (!is_null($this->opacity)) $layer .= '    OPACITY '.intval($this->opacity).PHP_EOL;
    if (!empty($this->classitem)) $layer .= '    CLASSITEM "'.$this->classitem.'"'.PHP_EOL;
    if (!empty($this->labelitem)) $layer .= '    LABELITEM "'.$this->labelitem.'"'.PHP_EOL;
    foreach ($this->_classes as $class) $layer .= $class->write();
    $layer .= '  END # LAYER'.PHP_EOL;

    return $layer;
  }

  private function read($array) {
    $layer = FALSE; $layer_projection = FALSE; $layer_class = FALSE; $layer_metadata = FALSE;

    foreach ($array as $_sz) {
      $sz = trim($_sz);

      if (preg_match('/^LAYER$/i', $sz)) $layer = TRUE;
      else if ($layer && preg_match('/^END( # LAYER)?$/i', $sz)) $layer = FALSE;

      else if ($layer && preg_match('/^PROJECTION$/i', $sz)) $layer_projection = TRUE;
      else if ($layer && $layer_projection && preg_match('/^END( # PROJECTION)?$/i', $sz)) $layer_projection = FALSE;
      else if ($layer && $layer_projection && preg_match('/^"init=(.+)"$/i', $sz, $matches)) $this->projection = $matches[1];

      else if ($layer && preg_match('/^CLASS$/i', $sz)) { $layer_class = TRUE; $class[] = $sz; }
      else if ($layer && $layer_class && preg_match('/^END( # CLASS)?$/i', $sz)) { $class[] = $sz; $this->addClass(new LayerClass($class)); $layer_class = FALSE; unset($class); }
      else if ($layer && $layer_class) { $class[] = $sz; }

      else if ($layer && preg_match('/^METADATA$/i', $sz)) { $layer_metadata = TRUE; }
      else if ($layer && $layer_metadata && preg_match('/^END( # METADATA)?$/i', $sz)) { $layer_metadata = FALSE; }
      else if ($layer && $layer_metadata && preg_match('/^"(.+)"\s"(.+)"$/i', $sz, $matches)) { $this->metadata[$matches[1]] = $matches[2]; }

      else if ($layer && preg_match('/^STATUS (.+)$/i', $sz, $matches)) $this->status = self::convertStatus($matches[1]);
      else if ($layer && preg_match('/^TYPE (.+)$/i', $sz, $matches)) $this->type = self::convertType($matches[1]);
      else if ($layer && preg_match('/^NAME "(.+)"$/i', $sz, $matches)) $this->name = $matches[1];
      else if ($layer && preg_match('/^CLASSITEM "(.+)"$/i', $sz, $matches)) $this->classitem = $matches[1];
      else if ($layer && preg_match('/^CONNECTIONTYPE (.+)$/i', $sz, $matches)) $this->connectiontype = self::convertConnectiontype($matches[1]);
      else if ($layer && preg_match('/^CONNECTION "(.+)"$/i', $sz, $matches)) $this->connection = $matches[1];
      else if ($layer && preg_match('/^DATA "(.+)"$/i', $sz, $matches)) $this->data = $matches[1];
      else if ($layer && preg_match('/^FILTER "(.+)"$/i', $sz, $matches)) $this->filter = $matches[1];
      else if ($layer && preg_match('/^FILTERITEM "(.+)"$/i', $sz, $matches)) $this->filteritem = $matches[1];
      else if ($layer && preg_match('/^GROUP "(.+)"$/i', $sz, $matches)) $this->group = $matches[1];
      else if ($layer && preg_match('/^LABELITEM "(.+)"$/i', $sz, $matches)) $this->labelitem = $matches[1];
      else if ($layer && preg_match('/^MAXSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->maxscaledenom = $matches[1];
      else if ($layer && preg_match('/^MINSCALEDENOM ([0-9\.]+)$/i', $sz, $matches)) $this->minscaledenom = $matches[1];
      else if ($layer && preg_match('/^OPACITY ([0-9]+)$/i', $sz, $matches)) $this->opacity = $matches[1];
      else if ($layer && preg_match('/^TILEITEM "(.+)"$/i', $sz, $matches)) $this->tileitem = $matches[1];
      else if ($layer && preg_match('/^TOLERANCE ([0-9\.]+)$/i', $sz, $matches)) $this->tolerance = $matches[1];
      else if ($layer && preg_match('/^TOLERANCEUNITS (.+)$/i', $sz, $matches)) $this->toleranceunits = $matches[1];
      else if ($layer && preg_match('/^UNITS (.+)$/i', $sz, $matches)) $this->units = self::convertUnits($matches[1]);
    }
  }

  private function convertConnectiontype($c = NULL) {
    $connectiontypes = array(
      self::CONNECTIONTYPE_CONTOUR => 'CONTOUR',
      self::CONNECTIONTYPE_LOCAL => 'LOCAL',
      self::CONNECTIONTYPE_OGR => 'OGR',
      self::CONNECTIONTYPE_ORACLESPATIAL => 'ORACLESPATIAL',
      self::CONNECTIONTYPE_PLUGIN => 'PLUGIN',
      self::CONNECTIONTYPE_POSTGIS => 'POSTGIS',
      self::CONNECTIONTYPE_SDE => 'SDE',
      self::CONNECTIONTYPE_UNION => 'UNION',
      self::CONNECTIONTYPE_UVRASTER => 'UVRASTER',
      self::CONNECTIONTYPE_WFS => 'WFS',
      self::CONNECTIONTYPE_WMS => 'WMS',
    );

    if (is_null($c)) return $connectiontypes[$this->connectiontype];
    else if (is_numeric($c)) return (isset($connectiontypes[$c]) ? $connectiontypes[$c] : FALSE);
    else return array_search($c, $connectiontypes);
  }
  private function convertStatus($s = NULL) {
    $statuses = array(
      self::STATUS_ON  => 'ON',
      self::STATUS_OFF => 'OFF'
    );

    if (is_null($s)) return $statuses[$this->status];
    else if (is_numeric($s)) return (isset($statuses[$s]) ? $statuses[$s] : FALSE);
    else return array_search($s, $statuses);
  }
  private function convertType($t = NULL) {
    $types = array(
      self::TYPE_POINT => 'POINT',
      self::TYPE_LINE => 'LINE',
      self::TYPE_POLYGON => 'POLYGON',
      self::TYPE_RASTER => 'RASTER',
      self::TYPE_QUERY => 'QUERY',
      self::TYPE_CIRCLE => 'CIRCLE',
      self::TYPE_TILEINDEX => 'TILEINDEX',
      self::TYPE_CHART => 'CHART',
    );

    if (is_null($t)) return $types[$this->type];
    else if (is_numeric($t)) return (isset($types[$t]) ? $types[$t] : FALSE);
    else return array_search($t, $types);
  }
  private function convertUnits($u = NULL) {
    $units = array(
      self::UNITS_INCHES        => 'INCHES',
      self::UNITS_FEET          => 'FEET',
      self::UNITS_MILES         => 'MILES',
      self::UNITS_METERS        => 'METERS',
      self::UNITS_KILOMETERS    => 'KILOMETERS',
      self::UNITS_DD            => 'DD',
      self::UNITS_PIXELS        => 'PIXELS',
      self::UNITS_NAUTICALMILES => 'NAUTICALMILES'
    );

    if (is_null($u)) return $units[$this->units];
    else if (is_numeric($u)) return (isset($units[$u]) ? $units[$u] : FALSE);
    else return array_search($u, $units);
  }
}