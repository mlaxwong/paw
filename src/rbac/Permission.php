<?php
namespace paw\rbac;

use Yii;
use yii\base\Component;

class Permission extends Component 
{
	const KEY_PERMISSION_CLASSNAME 	= 'className';
	const KEY_PERMISSION_TYPE 		= 'type';
	const KEY_PERMISSION_ACTION 	= 'action';

	const NAME_FORMAT = '{' . self::KEY_PERMISSION_CLASSNAME . '}[{' . self::KEY_PERMISSION_TYPE . '}:{' . self::KEY_PERMISSION_ACTION . '}]';
	const NAME_FORMAT_FIELD_MATCH = '#\{(.*?)\}#';

	protected $_rule;
	protected $_ruleName;
	protected $_description;
	protected $auth = 'authManager';
	protected $params = [];

	public static function get($action, $className = null)
	{
		return new Permission([
			self::KEY_PERMISSION_CLASSNAME 	=> $className,
			self::KEY_PERMISSION_ACTION 	=> $action,
		]);
	}

	public static function of($action, $className = null)
	{
		if ($className instanceof \yii\base\Object) {
			$className = $className::className();
		} else if (is_object($className)) {
			$className = get_class($className);
		}
		
		return new Permission([
			self::KEY_PERMISSION_CLASSNAME 	=> $className,
			self::KEY_PERMISSION_ACTION 	=> $action,
		]);
	}

	public function type($type)
	{
		$this->params[self::KEY_PERMISSION_TYPE] = $type;
		return $this;
	}

	public function __construct($params = array())
	{
		$this->auth = Yii::$app->get('authManager');
		$this->params = $params;
	}

	public function __get($key)
	{
		if (isset($this->getPermission()->{$key})) {
			return $this->getPermission()->{$key};
		}
		return parent::__get($key);
	}

	public function __set($key, $value)
	{
		$this->getPermission()->{$key} = $value;
	}
	
    public function setRuleName($ruleName) 
    {
		$this->_ruleName = $ruleName;
	}
	
    public function setRule($rule) 
    {
		if ($rule instanceof \yii\rbac\Rule) {
			$this->_rule = $rule;
		} else {
			$this->_rule = Yii::createObject($rule);
		}
		$hash = md5(serialize($this->_rule));
		$this->_rule->name .= '['.$hash.']';
		$this->_rule->name = $hash;
		
		if ($this->auth->getRule($this->_rule->name) === null) {
			$this->auth->add($this->_rule);
		}
		
		$this->setRuleName($this->_rule->name);
	}
	
    public function getRule() 
    {
		return $this->_rule;
	}
	
    public function getRuleName() 
    {
		return $this->_ruleName;
	}
	
    public function setDescription($desciption) 
    {
		$this->_description = $desciption;
		return $this;
	}
	
    public function getDescription() 
    {
		return $this->_description;
	}
	
    public function getName() 
    {
		return self::buildName($this->params);
	}
	
    public function toYiiPermission() 
    {
        $permission = $this->auth->createPermission($this->name);
        $permission->description = $this->description;
		$permission->ruleName = $this->ruleName;
		return $permission;
	}

	public function getPermission()
	{
		$value = [];
		foreach (static::getFormatFieldsName() as $field) $value[] = isset($this->params[$field]) ? $this->params[$field] : '';

		$permissionName = self::buildName($this->params);

		return $this->auth->getPermission($permissionName);
	}

	private static function getFormatFieldsName()
	{
		preg_match_all(self::NAME_FORMAT_FIELD_MATCH, self::NAME_FORMAT, $match);
		return $match[1];
	}

	private static function getFormatFields()
	{
		preg_match_all(self::NAME_FORMAT_FIELD_MATCH, self::NAME_FORMAT, $match);
		return $match[0];
	}

	public static function buildName($params)
	{
		foreach (static::getFormatFieldsName() as $field) $value[] = isset($params[$field]) ? $params[$field] : '';

		return str_replace(self::getFormatFields(), $value, self::NAME_FORMAT);
	}

	public static function breakName($name)
	{
		$className = substr($name, 0, strpos($name, '['));

        preg_match('#\[(.*?)\]#', $name, $match);
        $typeAndAction =  explode(':', $match[1]);

        $type = $typeAndAction[0];
        $action = $typeAndAction[1];

        return [
            'className' => $className,
            'type' => $type,
            'action' => $action,
        ];
	}

	public static function all()
	{
		$auth = Yii::$app->get('authManager');
		return $auth->getPermissions();
	}
}