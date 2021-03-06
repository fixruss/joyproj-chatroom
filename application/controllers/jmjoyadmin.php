<?php 

class JmjoyAdmin extends CI_Controller {

	public function index() {
		if ($this->checkSignIn()) {
			redirect('jmjoyadmin/base');
		}
		$data['username'] = $this->input->cookie('admin_form_username');
		setcookie('admin_form_username', '', time() - 3600);
		$data['msg'] = $this->input->cookie('admin_form_msg');
		setcookie('admin_form_msg', '', time() - 3600);

		$this->load->view('admin/index', $data);
	}
	
	public function base() {
		$this->forbiddenIfNotSignIn();
		$this->load->view('admin/base');
	}
	
	public function home() {
		$this->forbiddenIfNotSignIn();
		$this->load->view('admin/home');
	}
	
	public function category($id = 0) {
		$this->forbiddenIfNotSignIn();
		
		$data['top'] = $this->getCategoryByParentId(0);
		
		if ($id) {
			$data['sub'] = $this->getCategoryByParentId(intval($id));
		}
		
		$data['id'] = $id;
		
		$this->load->view('admin/category', $data);
	}	
	
	public function handleAddCategory($id = 0) {
		$this->forbiddenIfNotSignIn();
		
		$name = $this->input->post('name');
		$parent_id = intval($this->input->post('parent_id'));
		
		$sql = 'insert into lc_category (name, parent_id) values(?, ?)';
		$this->db->query($sql, array($name, $parent_id));
		$insert_id = $this->db->insert_id();
		
		if ($parent_id != 0) {
			$sql = "create table lc_room_auto_{$insert_id} (
					id bigint unsigned primary key auto_increment,
					uid int unsigned not null default 0,
					content tinytext,
					ctime int unsigned not null default 0
			)";
			$this->db->query($sql);
			
			$sql = "create table lc_room_online_{$insert_id} (
					uid int unsigned not null default 0,
					username varchar(8) not null default '',
					ctime int unsigned not null default 0
			)";
			$this->db->query($sql);
		}
		
		redirect('jmjoyadmin/category/' . $id);
	}
	
	public function user() {
		$this->forbiddenIfNotSignIn();
		$this->load->view('admin/user');
	}
	
	public function listUser() {
		$page = intval($this->input->post('page'));
		$rows = intval($this->input->post('rows'));
		$offset = ($page - 1) * $rows;
		
		$sql = 'select count(*) c from lc_user';
		$row = $this->db->query($sql)->row();
		$data['total'] = $row->c;
		
		$sql = 'select * from lc_user 
				order by id desc 
				limit ?, ?';
		$query = $this->db->query($sql, array($offset, $rows));
		$resArr = $query->result_array();
		foreach ($resArr as $key => $row) {
			$resArr[$key]['ctime'] = date('Y-m-d H:i:s', $row['ctime']);
		}
		$data['rows'] = $resArr;
		
		echo json_encode($data);
	}
	
	public function room() {
		$this->forbiddenIfNotSignIn();
		$this->load->view('admin/room');
	}
	
	public function info() {
		$this->forbiddenIfNotSignIn();
		phpinfo();
	}
	
	public function handleSignIn() {
		$username = $this->input->post('username');
		if ($username == "") {
			setcookie('admin_form_username', $username, 0);
			setcookie('admin_form_msg', '账号不能为空', 0);
			return redirect('jmjoyadmin/index');
		}
		$password =  $this->input->post('password');
		if ($password == "") {
			setcookie('admin_form_username', $username, 0);
			setcookie('admin_form_msg', '密码不能为空', 0);
			return redirect('jmjoyadmin/index');
		}
		$sql = 'select id, username, password 
				from lc_admin 
				where username = ?
				limit 1';
		$query = $this->db->query($sql, $username);
		if ($query->num_rows() <= 0) {
			setcookie('admin_form_username', $username, 0);
			setcookie('admin_form_msg', '账号不存在', 0);
			return redirect('jmjoyadmin/index');
		}
		$row = $query->row();
		if ($row->password != sha1(md5($password))) {
			setcookie('admin_form_username', $username, 0);
			setcookie('admin_form_msg', '密码错误', 0);
			return redirect('jmjoyadmin/index');			
		}
		session_start();
		$_SESSION['admin']['id'] = $row->id;
		$_SESSION['admin']['id'] = $row->username;
		redirect('jmjoyadmin/base');
	}
	
	public function handleSignOut() {
		session_start();
		unset($_SESSION['admin']);
		redirect('jmjoyadmin/index');
	}
	
	protected function checkSignIn() {
		session_start();
		if (!isset($_SESSION['admin']['id'])) {
			return false;
		}
		return true;
	}

	protected function forbiddenIfNotSignIn() {
		if (!$this->checkSignIn()) {
			redirect('jmjoyadmin/index');
			die();
		}
	}
	
	protected function getCategoryByParentId($parent_id) {
		$sql = "select * from lc_category where parent_id = ?";
		$query = $this->db->query($sql, $parent_id);
		return $query->result();
	}
	
}
