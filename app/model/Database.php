<?php
/**
* 基础数据库访问
* 
*/
class Database extends DB
{
	protected static $initCmd=array('SET NAMES UTF8');
	protected static $initCmdSqlite=array('PRAGMA SYNCHRONOUS=OFF','PRAGMA CACHE_SIZE=8000','PRAGMA TEMP_STORE=MEMORY');
	private  $orm;

	public function __construct($cfg=null,$column='*')
	{
		$this->orm['table']=self::table();
		if(!is_null($cfg))
		{
			if(is_numeric($cfg))
			{
				$this->orm['instance']=$this->selectById($this->orm['table'],$cfg,$column);
			}
			else
			{
				$this->orm['instance']=$this->selectWhere($this->orm['table'],$cfg,null,$column);
			}
			$this->orm['instance']=$this->orm['instance']?$this->orm['instance']:false;
		}
	}

	public static function table()
	{
		return get_called_class();
	}

	final public static function selectById($table=null,$id,$column='*')
	{
		$id=intval($id);
		$sql="SELECT {$column} FROM {$table} WHERE id={$id} ";
		return self::getLine($sql);
	}

	final public static function deleteById($table,$id)
	{
		$id=intval($id);
		$sql="DELETE FROM {$table} WHERE id={$id} ";
		return self::runSql($sql);
	}

	final public static function updateById($table,$id,$data)
	{
		$id=intval($id);
		$v=array();
		foreach ($data as $key => $value)
		{
			$value=self::quote($value);
			$v[]=$key.'='.$value;
		}
		$strv=implode(',',$v);  
		$sql="UPDATE {$table} SET {$strv} WHERE id ='{$id}' ";
		return self::runSql($sql);
	}

	final public static function insertData($table,$data)
	{
		$k=$v=array();
		foreach ($data as $key => $value)
		{
			$k[]='`'.$key.'`';
			$v[]=self::quote($value);
		}
		$strv=implode(',',$v);    
		$strk=implode(',',$k);
		$sql="INSERT INTO {$table} ({$strk}) VALUES ({$strv})";
		if(self::runSql($sql))
		{
			return self::lastId();
		}
		return false;
	}

	final public static function selectWhere($table,$where=null,$orderlimit=null,$column='*')
	{
		if($where)
		{
			if(is_array($where))
			{
				$k=array();
				foreach ($where as $key => $value) 
				{
					$value=self::quote($value);
					$k[]='(`'.$key.'`='.$value.')';
				}
				$strk=implode(" AND ",$k);
			}
			else
			{
				$strk=$where;
			}
			$sql="SELECT {$column} FROM {$table} WHERE ({$strk}) ";
		}
		else
		{
			$sql="SELECT {$column} FROM {$table} ";
		}
		if($orderlimit)
		{
			$sql.=$orderlimit;
		}
		return self::getData($sql);
	}

	final public static function deleteWhere($table,$where=null)
	{
		if($where)
		{
			if(is_array($where))
			{
				$k=array();
				foreach ($where as $key => $value) 
				{
					$value=self::quote($value);
					$k[]='(`'.$key.'`='.$value.')';
				}
				$strk=implode(" AND ",$k);
			}
			else
			{
				$strk=$where;
			}
			$sql="DELETE FROM {$table} WHERE ({$strk}) ";
		}
		else
		{
			$sql="DELETE FROM {$table} ";
		}
		return self::runSql($sql);
	}

	final public static function updateWhere($table,$where,$data)
	{
		$k=$v=array();
		if(is_array($where))
		{
			foreach ($where as $key => $value) 
			{
				$value=self::quote($value);
				$k[]='(`'.$key.'`='.$value.')';
			}
			$strk=implode(" AND ",$k);
		}
		else
		{
			$strk=$where;
		}
		foreach ($data as $key => $value) 
		{
			$v[]=$key.'='."'".$value."'";
		}
		$strv=implode(' , ',$v);
		$sql="UPDATE {$table} SET {$strv} WHERE ({$strk})";
		return self::runSql($sql);
	}

	/***
	 *	$data=array(
	 *			array('name'=>'s1','pass'=>'p1','email'=>'123@qq.com'),
	 *			array('name'=>'s2','pass'=>'p2','email'=>'456@qq.com'),
	 *			array('name'=>'s3','pass'=>'p3','email'=>'789@qq.com')
	 *	);
	**/
	final public static function multInsert($table,$data,Closure $callback=null)
	{
		try
		{
			self::beginTransaction();
			$columns=array_keys($data[0]);
			$columnStr=implode(',',$columns);
			$valueStr=implode(',:', $columns);
			$sql="INSERT INTO {$table} ({$columnStr}) VALUES (:{$valueStr})";
			$stmt=self::prepare($sql);
			foreach ($columns as $k)
			{
				$stmt->bindParam(":{$k}",$$k);
			}
			foreach ($data as $i=>$item)
			{
				foreach ($item as $k => $v)
				{
					$$k=$v;
				}
				$stmt->execute();
			}
			return self::commit();
		}
		catch(PDOException $e)
		{
			self::rollback();
			return $callback?$callback($e):false;
		}

	}
	/***
	 *	批量更新   
	 *	$data=array(
	 *			'18'=>array('name'=>'name18','pass'=>'11'),
	 *			'19'=>array('name'=>'name19','pass'=>'22')
	 *	);
	 **/
	final public static function multUpdate($table,$data,Closure $callback=null)
	{
		try
		{
			self::beginTransaction();
			$keys=array_keys(current($data));
			$v=array();
			foreach ($keys as $k) 
			{
				$v[]=$k.'='.":".$k."";
			}
			$strv=implode(',', $v);
			$sql="UPDATE {$table} SET {$strv} WHERE id=:id";
			$stmt=self::prepare($sql);
			foreach ($keys as $k)
			{
				$stmt->bindParam(":{$k}", $$k);
			}
			$stmt->bindParam(":id", $id);
			foreach ($data as $id => $item)
			{
				foreach ($item as $k => $v)
				{
					$$k=$v;
				}
				$stmt->execute();
			}
			return self::commit();
		}
		catch(PDOException $e)
		{
			 self::rollback();
			 return $callback?$callback($e):false;
		}
	}

	final public static function multDelete($table,$inStr,$column='id')
	{
		$str=is_array($inStr)?implode(',', $inStr):$inStr;
		$sql="DELETE FROM {$table} WHERE {$column} IN ({$str})";
		return self::runSql($sql);
	}

	final public static function multSelect($table,$inStr,$selectcolumn='*',$column='id')
	{
		$str=is_array($inStr)?implode(',', $inStr):$inStr;
		$sql="SELECT {$selectcolumn} FROM {$table} WHERE {$column} IN ({$str}) ";
		$ret=self::getData($sql);
		$res=array();
		foreach ($ret as $item)
		{
			$id=$item[$column];
			unset($item[$column]);
			$res[$id]=count($item)==1?current($item):$item;
		}
		return $res;
	}

	final public static function incrById($table,$column,$id,$num=1)
	{
		$id=intval($id);
		$sql="UPDATE {$table} SET {$column}={$column}+{$num} WHERE id={$id} ";
		return self::runSql($sql);
	}

	final public static function decrById($table,$column,$id,$num=1)
	{
		$id=intval($id);
		$sql="UPDATE {$table} SET {$column}={$column}-{$num} WHERE id={$id} ";
		return self::runSql($sql);
	}

	final public static function getList($table,$page=1,$where=null,$orderby='id desc',$per=20,$selectcolumn='*')
	{
		$offset=max(0,($page-1)*$per);
		if($where)
		{
			if(is_array($where))
			{
				$k=array();
				foreach ($where as $key => $value) 
				{
					$value=self::quote($value);
					$k[]='(`'.$key.'`='.$value.')';
				}
				$strk=implode(" AND ",$k);
			}
			else
			{
				$strk=$where;
			}
			$list="SELECT {$selectcolumn} FROM {$table} WHERE  ({$strk})  ORDER BY {$orderby} LIMIT {$offset},{$per} ";
			$pages="SELECT COUNT(1) FROM {$table} WHERE ({$strk}) ";
		}
		else
		{
			$list="SELECT {$selectcolumn} FROM {$table} ORDER BY {$orderby} LIMIT {$offset},{$per} ";
			$pages="SELECT COUNT(1) FROM {$table} ";
		}
		$list=self::getData($list);
		$pages=ceil(self::getVar($pages)/$per);
		return array('list'=>$list,'page'=>$pages,'current'=>$page,'prev'=>max(1,$page-1),'next'=>min($pages,$page+1));
	}

	final public static function like($table,$column,$like,$selectcolumn='*',$num=50)
	{
		$sql="SELECT {$selectcolumn} FROM {$table} WHERE {$column} LIKE '%{$like}%' LIMIT {$num}";
		return self::getData($sql);
	}

	final public static function count($table,$where=null)
	{
		if($where)
		{
			if(is_array($where))
			{
				$k=array();
				foreach ($where as $key => $value) 
				{
					$k[]='(`'.$key.'`="'.$value.'")';
				}
				$strk=implode(" AND ",$k);
			}
			else
			{
				$strk=$where;
			}
			$where=" WHERE ({$strk}) ";
		}
		$sql="SELECT COUNT(1) FROM {$table} {$where} ";
		return self::getVar($sql);
		
	}

	final public function __get($key)
	{
		return isset($this->orm['instance'][$key])?$this->orm['instance'][$key]:null;
	}

	final public function __set($key,$value)
	{
		if(empty($this->orm['instance']))
		{
			$this->orm['data'][$key]=$value;
		}
		else
		{
			if(array_key_exists($key, $this->orm['instance']) && ($this->orm['instance'][$key] !== $value))
			{
				$this->orm['data'][$key]=$value;
				$this->orm['instance'][$key]=$value;
			}
		}
	}

	final public function __invoke($data=null)
	{
		if($data)
		{
			if(!isset($this->orm['instance']))
			{
				return $this->insertData($this->orm['table'],$data);
			}
			else if($this->orm['instance']===false)
			{
				return false;
			}
			else
			{
				return $this->updateWhere($this->orm['table'],$this->orm['instance'],$data);
			}
		}
		else
		{
			return isset($this->orm['instance'])?$this->orm['instance']:null;
		}
		
	}

	final public function __toString()
	{
		return isset($this->orm['instance'])?var_export($this->orm['instance'],true):null;
	}
	
	final public function save($data=null)
	{
		if(!isset($this->orm['instance']))
		{
			return $this->insertData($this->orm['table'],$this->orm['data']);
		}
		else if(!empty($this->orm['data']))
		{
			if($this->orm['instance']===false)
			{
				return false;
			}
			else
			{
				return $this->updateWhere($this->orm['table'],$this->orm['instance'],$this->orm['data']);
			}
		}
		else
		{
			return isset($this->orm['instance'][0])?false:true;
		}
	}

	final public function delete()
	{
		if(!empty($this->orm['instance']))
		{
			return $this->deleteWhere($this->orm['table'],$this->orm['instance']);
		}
	}
	
}
// end class database

