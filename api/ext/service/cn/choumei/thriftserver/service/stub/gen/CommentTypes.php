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
 * 发表item评论返回的信息
 */
class SendItemCommentBean extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var int
   */
  public $itemcommentId = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'itemId',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'salonId',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'itemcommentId',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SendItemCommentBean';
  }

  public function read($input)
  {
    return $this->_read('SendItemCommentBean', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SendItemCommentBean', self::$_TSPEC, $output);
  }

}

class SendItemCommentResult extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\SendItemCommentBean
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
          'class' => '\cn\choumei\thriftserver\service\stub\gen\SendItemCommentBean',
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SendItemCommentResult';
  }

  public function read($input)
  {
    return $this->_read('SendItemCommentResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SendItemCommentResult', self::$_TSPEC, $output);
  }

}

/**
 * 查询item评论详情返回的信息
 */
class ItemCommentDetailBean extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var string
   */
  public $itemName = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var string
   */
  public $salonName = null;
  /**
   * @var int
   */
  public $addTime = null;
  /**
   * @var int
   */
  public $satisfyType = null;
  /**
   * @var int
   */
  public $satisfyRemark = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $img = null;
  /**
   * @var string
   */
  public $reply = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'itemId',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'itemName',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'salonId',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'salonName',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'addTime',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'satisfyType',
          'type' => TType::I32,
          ),
        7 => array(
          'var' => 'satisfyRemark',
          'type' => TType::I32,
          ),
        8 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        9 => array(
          'var' => 'img',
          'type' => TType::STRING,
          ),
        10 => array(
          'var' => 'reply',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ItemCommentDetailBean';
  }

  public function read($input)
  {
    return $this->_read('ItemCommentDetailBean', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ItemCommentDetailBean', self::$_TSPEC, $output);
  }

}

class ItemCommentDetailResult extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\ItemCommentDetailBean
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
          'class' => '\cn\choumei\thriftserver\service\stub\gen\ItemCommentDetailBean',
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ItemCommentDetailResult';
  }

  public function read($input)
  {
    return $this->_read('ItemCommentDetailResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ItemCommentDetailResult', self::$_TSPEC, $output);
  }

}

/**
 * 查询item评论列表返回的信息
 */
class ItemCommentListBean extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $orderTicketId = null;
  /**
   * @var int
   */
  public $useTime = null;
  /**
   * @var int
   */
  public $isComment = null;
  /**
   * @var string
   */
  public $ticketNo = null;
  /**
   * @var double
   */
  public $priceAll = null;
  /**
   * @var string
   */
  public $salonName = null;
  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var string
   */
  public $itemName = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var int
   */
  public $satisfyType = null;
  /**
   * @var string
   */
  public $listExtra = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'orderTicketId',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'useTime',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'isComment',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'ticketNo',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'priceAll',
          'type' => TType::DOUBLE,
          ),
        6 => array(
          'var' => 'salonName',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'itemId',
          'type' => TType::I32,
          ),
        8 => array(
          'var' => 'itemName',
          'type' => TType::STRING,
          ),
        9 => array(
          'var' => 'salonId',
          'type' => TType::I32,
          ),
        10 => array(
          'var' => 'satisfyType',
          'type' => TType::I32,
          ),
        11 => array(
          'var' => 'listExtra',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ItemCommentListBean';
  }

  public function read($input)
  {
    return $this->_read('ItemCommentListBean', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ItemCommentListBean', self::$_TSPEC, $output);
  }

}

class ItemCommentListResult extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\ItemCommentListBean[]
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
            'class' => '\cn\choumei\thriftserver\service\stub\gen\ItemCommentListBean',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ItemCommentListResult';
  }

  public function read($input)
  {
    return $this->_read('ItemCommentListResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ItemCommentListResult', self::$_TSPEC, $output);
  }

}

/**
 * 发表HairStylistComment返回的信息
 */
class SendHairStylistCommentResult extends TBase {
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
   * @var bool
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
          'type' => TType::BOOL,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SendHairStylistCommentResult';
  }

  public function read($input)
  {
    return $this->_read('SendHairStylistCommentResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SendHairStylistCommentResult', self::$_TSPEC, $output);
  }

}

/**
 * 查询StylistCommentList评论列表返回的信息
 */
class StylistCommentListBean extends TBase {
  static $_TSPEC;

  /**
   * @var string
   */
  public $userImg = null;
  /**
   * @var string
   */
  public $userName = null;
  /**
   * @var int
   */
  public $level = null;
  /**
   * @var int
   */
  public $addTime = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $imgSrc = null;
  /**
   * @var int
   */
  public $totalNum = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'userImg',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'userName',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'level',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'addTime',
          'type' => TType::I32,
          ),
        5 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'imgSrc',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'totalNum',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'StylistCommentListBean';
  }

  public function read($input)
  {
    return $this->_read('StylistCommentListBean', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('StylistCommentListBean', self::$_TSPEC, $output);
  }

}

class StylistCommentListResult extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\StylistCommentListBean[]
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
            'class' => '\cn\choumei\thriftserver\service\stub\gen\StylistCommentListBean',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'StylistCommentListResult';
  }

  public function read($input)
  {
    return $this->_read('StylistCommentListResult', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('StylistCommentListResult', self::$_TSPEC, $output);
  }

}

class ItemComment extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $commentId = null;
  /**
   * @var int
   */
  public $userId = null;
  /**
   * @var int
   */
  public $orderTicketId = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var int
   */
  public $stars = null;
  /**
   * @var int
   */
  public $hairstylistId = null;
  /**
   * @var int
   */
  public $satisfyType = null;
  /**
   * @var int
   */
  public $satisfyRemark = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $imgsrc = null;
  /**
   * @var string
   */
  public $reply = null;
  /**
   * @var int
   */
  public $addTime = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'commentId',
          'type' => TType::I64,
          ),
        2 => array(
          'var' => 'userId',
          'type' => TType::I64,
          ),
        3 => array(
          'var' => 'orderTicketId',
          'type' => TType::I64,
          ),
        4 => array(
          'var' => 'salonId',
          'type' => TType::I64,
          ),
        5 => array(
          'var' => 'itemId',
          'type' => TType::I64,
          ),
        6 => array(
          'var' => 'stars',
          'type' => TType::I32,
          ),
        7 => array(
          'var' => 'hairstylistId',
          'type' => TType::I64,
          ),
        8 => array(
          'var' => 'satisfyType',
          'type' => TType::I32,
          ),
        9 => array(
          'var' => 'satisfyRemark',
          'type' => TType::I32,
          ),
        10 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        11 => array(
          'var' => 'imgsrc',
          'type' => TType::STRING,
          ),
        12 => array(
          'var' => 'reply',
          'type' => TType::STRING,
          ),
        13 => array(
          'var' => 'addTime',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'ItemComment';
  }

  public function read($input)
  {
    return $this->_read('ItemComment', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('ItemComment', self::$_TSPEC, $output);
  }

}

class GetSalonCommentRet extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\ItemComment[]
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
            'class' => '\cn\choumei\thriftserver\service\stub\gen\ItemComment',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetSalonCommentRet';
  }

  public function read($input)
  {
    return $this->_read('GetSalonCommentRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetSalonCommentRet', self::$_TSPEC, $output);
  }

}

class GetItemCommentRet extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\ItemComment[]
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
            'class' => '\cn\choumei\thriftserver\service\stub\gen\ItemComment',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetItemCommentRet';
  }

  public function read($input)
  {
    return $this->_read('GetItemCommentRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetItemCommentRet', self::$_TSPEC, $output);
  }

}

class GetCommentRet extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\ItemComment
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
          'class' => '\cn\choumei\thriftserver\service\stub\gen\ItemComment',
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetCommentRet';
  }

  public function read($input)
  {
    return $this->_read('GetCommentRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetCommentRet', self::$_TSPEC, $output);
  }

}

class BountyCommentThrift extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $bcId = null;
  /**
   * @var string
   */
  public $btSn = null;
  /**
   * @var int
   */
  public $userId = null;
  /**
   * @var int
   */
  public $hairstylistId = null;
  /**
   * @var int
   */
  public $type = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $imgSrc = null;
  /**
   * @var string
   */
  public $notSatisfyStr = null;
  /**
   * @var int
   */
  public $addTime = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'bcId',
          'type' => TType::I64,
          ),
        2 => array(
          'var' => 'btSn',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'userId',
          'type' => TType::I64,
          ),
        4 => array(
          'var' => 'hairstylistId',
          'type' => TType::I64,
          ),
        5 => array(
          'var' => 'type',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'imgSrc',
          'type' => TType::STRING,
          ),
        8 => array(
          'var' => 'notSatisfyStr',
          'type' => TType::STRING,
          ),
        9 => array(
          'var' => 'addTime',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'BountyCommentThrift';
  }

  public function read($input)
  {
    return $this->_read('BountyCommentThrift', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('BountyCommentThrift', self::$_TSPEC, $output);
  }

}

class GetBtCommentByStylistIdRet extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\BountyCommentThrift[]
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
            'class' => '\cn\choumei\thriftserver\service\stub\gen\BountyCommentThrift',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetBtCommentByStylistIdRet';
  }

  public function read($input)
  {
    return $this->_read('GetBtCommentByStylistIdRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetBtCommentByStylistIdRet', self::$_TSPEC, $output);
  }

}

class GetBtCommentCountByStylistIdRet extends TBase {
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
   * @var int
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
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetBtCommentCountByStylistIdRet';
  }

  public function read($input)
  {
    return $this->_read('GetBtCommentCountByStylistIdRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetBtCommentCountByStylistIdRet', self::$_TSPEC, $output);
  }

}

class GetBountyCommentBySnRet extends TBase {
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
   * @var \cn\choumei\thriftserver\service\stub\gen\BountyCommentThrift
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
          'class' => '\cn\choumei\thriftserver\service\stub\gen\BountyCommentThrift',
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'GetBountyCommentBySnRet';
  }

  public function read($input)
  {
    return $this->_read('GetBountyCommentBySnRet', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('GetBountyCommentBySnRet', self::$_TSPEC, $output);
  }

}

class SendItemCommentParam extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $orderTicketId = null;
  /**
   * @var int
   */
  public $type = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var int
   */
  public $hairstylistid = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $imgSrc = null;
  /**
   * @var int
   */
  public $satisfyType = null;
  /**
   * @var int
   */
  public $satisfyRemark = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'orderTicketId',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'type',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'salonId',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'itemId',
          'type' => TType::I32,
          ),
        5 => array(
          'var' => 'hairstylistid',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'imgSrc',
          'type' => TType::STRING,
          ),
        8 => array(
          'var' => 'satisfyType',
          'type' => TType::I32,
          ),
        9 => array(
          'var' => 'satisfyRemark',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'SendItemCommentParam';
  }

  public function read($input)
  {
    return $this->_read('SendItemCommentParam', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('SendItemCommentParam', self::$_TSPEC, $output);
  }

}

class PostItemCommentParam extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $orderTicketId = null;
  /**
   * @var int
   */
  public $satisfyType = null;
  /**
   * @var int
   */
  public $remark = null;
  /**
   * @var string
   */
  public $content = null;
  /**
   * @var string
   */
  public $imgsrc = null;
  /**
   * @var int
   */
  public $userId = null;
  /**
   * @var int
   */
  public $salonId = null;
  /**
   * @var int
   */
  public $itemId = null;
  /**
   * @var int
   */
  public $stylistId = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'orderTicketId',
          'type' => TType::I64,
          ),
        2 => array(
          'var' => 'satisfyType',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'remark',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'imgsrc',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'userId',
          'type' => TType::I64,
          ),
        7 => array(
          'var' => 'salonId',
          'type' => TType::I64,
          ),
        8 => array(
          'var' => 'itemId',
          'type' => TType::I64,
          ),
        9 => array(
          'var' => 'stylistId',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'PostItemCommentParam';
  }

  public function read($input)
  {
    return $this->_read('PostItemCommentParam', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('PostItemCommentParam', self::$_TSPEC, $output);
  }

}

class AddBtCommentParam extends TBase {
  static $_TSPEC;

  /**
   * @var int
   */
  public $userId = null;
  /**
   * @var string
   */
  public $btSn = null;
  /**
   * @var int
   */
  public $hairstylistId = null;
  /**
   * @var int
   */
  public $type = null;
  /**
   * @var string
   */
  public $imgSrc = null;
  /**
   * @var string
   */
  public $notSatisfyStr = null;
  /**
   * @var int
   */
  public $addTime = null;
  /**
   * @var string
   */
  public $content = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'userId',
          'type' => TType::I64,
          ),
        2 => array(
          'var' => 'btSn',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'hairstylistId',
          'type' => TType::I64,
          ),
        4 => array(
          'var' => 'type',
          'type' => TType::I32,
          ),
        5 => array(
          'var' => 'imgSrc',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'notSatisfyStr',
          'type' => TType::STRING,
          ),
        7 => array(
          'var' => 'addTime',
          'type' => TType::I64,
          ),
        8 => array(
          'var' => 'content',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      parent::__construct(self::$_TSPEC, $vals);
    }
  }

  public function getName() {
    return 'AddBtCommentParam';
  }

  public function read($input)
  {
    return $this->_read('AddBtCommentParam', self::$_TSPEC, $input);
  }

  public function write($output) {
    return $this->_write('AddBtCommentParam', self::$_TSPEC, $output);
  }

}

