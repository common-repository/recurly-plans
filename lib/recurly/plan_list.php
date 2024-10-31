<?php

class Recurly_PlanList extends Recurly_Pager
{
  public static function get($params = null, $client = null)
  {
    $list = new Recurly_PlanList(Recurly_Client::PATH_PLANS, $client);
    $list->_loadFrom(Recurly_Client::PATH_PLANS, $params);
    return $list;
  }

  protected function getNodeName() {
    return 'plans';
  }
  
  /* Added by Meet Thosar*/
  public function getPlanXML() {
  	$list = new Recurly_PlanList(Recurly_Client::PATH_PLANS, $client);
    return $response = $list->_loadFromXML(Recurly_Client::PATH_PLANS, $params);    
  }
  /* Added by Meet Thosar*/
}
