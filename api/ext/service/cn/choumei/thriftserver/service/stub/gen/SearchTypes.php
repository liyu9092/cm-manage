<?php
namespace cn\choumei\thriftserver\service\stub\gen;

/**
 * Autogenerated by Thrift Compiler (0.9.2)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


/**
 * 搜索返回的店铺列表信息
 */
class SalonSearchBean extends TBase {
  static $_TSPEC;

  /**
   * @var string
   */
  public $salonname = null;
  /**
   * @var int
   */
  public $salonid = null;
  /**
   * @var int
   */
  public $zone = null;
  /**
   * @var string
   */
  public $addr = null;
  /**
   * @var string
   */
  public $tel = null;
  /**
   * @var double
   */
  public $addrlati = null;
  /**
   * @var double
   */
  public $addrlong = null;
  /**
   * @var int
   */
  public $commentnum = null;
  /**
   * @var int
   */
  public $satisfyOne = null;
  /**
   * @var int
   */
  public $satisfyTwo = null;
  /**
   * @var int
   */
  public $satisfyThree = null;
  /**
   * @var string
   */
  public $logo = null;
  /**
   * @var int
   */
  public $status = null;
  /**
   * @var double
   */
  public $discount = null;
  /**
   * @var int
   */
  public $district = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'salonname',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'salonid',
          'type' => TType::I64,
          ),
        3 => array(
          'var' => 'zone',
          'type' => TType::I64,
          ),
        4 => array(
          'var' => 'addr',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'tel',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'addrlati',
          'type' => TType::DOUBLE,
          ),
        7 => array(
          'var' => 'addrlong',
          'type' => TType::DOUBLE,
          ),
        9 => array(
          'var' => 'commentnum',
          'type' => TType::I64,
          ),
        10 => array(
          'var' => 'satisfyOne',
          'type' => TType::I64,
          ),
        11 => array(
          'var' => 'satisfyTwo',
          'type' => TType::I64,
          ),
        12 => array(
          'var' => 'satisfyThree',
          'type' => TType::I64,
          ),
        13 => array(
          'var' => 'logo',
          'type' => TType::STRING,
          ),
        14 => array(
          'var' => 'status',
          'type' => TType::I64,
          ),
        15 => array(
          'var' => 'discount',
          'type' => TType::DOUBLE,
          ),
        16 => array(
          'var' => 'district',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SalonSearchBean';
  }

  public function read($input)
  {
    return $this->_read('SalonSearchBean', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SalonSearchBean', self::$_TSPEC, $output);
  }

}

class SalonSearchData extends TBase {
  static $_TSPEC;

  /**
   * @var \cn\choumei\thriftserver\service\stub\gen\SalonSearchBean[]
   */
  public $salonSearchBeanList = null;
  /**
   * @var int
   */
  public $totalNum = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'salonSearchBeanList',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\cn\choumei\thriftserver\service\stub\gen\SalonSearchBean',
            ),
          ),
        2 => array(
          'var' => 'totalNum',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SalonSearchData';
  }

  public function read($input)
  {
    return $this->_read('SalonSearchData', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SalonSearchData', self::$_TSPEC, $output);
  }

}

class SalonSearchResult extends TBase {
  static $_TSPEC;

  /**
   * @var string
   */
  public $result = null;
  /**
   * @var int
   */
  public $errorCode = null;
  /**
   * @var string
   */
  public $errorMsg = null;
  /**
   * @var \cn\choumei\thriftserver\service\stub\gen\SalonSearchData
   */
  public $data = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'result',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'errorCode',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'errorMsg',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'data',
          'type' => TType::STRUCT,
          'class' => '\cn\choumei\thriftserver\service\stub\gen\SalonSearchData',
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SalonSearchResult';
  }

  public function read($input)
  {
    return $this->_read('SalonSearchResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SalonSearchResult', self::$_TSPEC, $output);
  }

}

class HairStylistInfoThrift extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $stylistId = null;
  /**
   * @var int
   */
  public $osType = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'stylistId',
          'type' => TType::I64,
          ),
        2 => array(
          'var' => 'osType',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'HairStylistInfoThrift';
  }

  public function read($input)
  {
    return $this->_read('HairStylistInfoThrift', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('HairStylistInfoThrift', self::$_TSPEC, $output);
  }

}

/**
 * 通过店铺等级和发型师等级查询造型师信息结果集
 */
class GetStylistByGradeRet extends TBase {
  static $_TSPEC;

  /**
   * @var string
   */
  public $result = null;
  /**
   * @var int
   */
  public $errorCode = null;
  /**
   * @var string
   */
  public $errorMsg = null;
  /**
   * @var \cn\choumei\thriftserver\service\stub\gen\HairStylistInfoThrift[]
   */
  public $data = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'result',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'errorCode',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'errorMsg',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'data',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => '\cn\choumei\thriftserver\service\stub\gen\HairStylistInfoThrift',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetStylistByGradeRet';
  }

  public function read($input)
  {
    return $this->_read('GetStylistByGradeRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetStylistByGradeRet', self::$_TSPEC, $output);
  }

}

