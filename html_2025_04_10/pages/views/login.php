<div class="login-wrapper" id="modalbg">
	<input class="hidden" type="checkbox" name="reg" id="checkreg"/>
	<div class="closemodal" id="close_login"><i class="fas fa-times"></i></div>
	<div class="auth-bg" id="forms_holder">
		<form class="authform" method="post" name="signin" id="login">
			<fieldset class="inputset" id="logininputs">
				<input class="cehcktoshowpass hidden" type="checkbox" name="showpass" id="showpass"/>
				<div class="input-holder">
					<label for="username"><i class="fas fa-user"></i></label>
					<input id="username" type="text" placeholder="Login" name="user" required/>
				</div>
				<div class="input-holder">
					<label for="password"><i class="fas fa-lock"></i></label>
					<input id="password" type="password" placeholder="Password" name="password"/>
				</div>
				<label class="showpass" for="showpass">Показать пароль&nbsp;<i class="fas fa-eye"></i></label>
			</fieldset>
			<div class="lineholder fullwidth">
				<input type="checkbox" name="remember" id="remember"/>
				<label class="lable" for="remember">Запомнить</label>
				<label class="lable active right" for="checkreg" title="Нажмите для регистрации">Нет учетной записи?</label>
			</div>
			<div class="flex-column tail">
				<button type="submit" class="btn on-the-glass">Войти</button>
			</div>
			<div class="auth-bg-blink"></div>
		</form>
		<!-- <div class="sideplane"></div> -->
		<form class="authform" method="post" name="signup" id="register">
			<fieldset class="inputset" id="registerinputs">
				<input class="cehcktoshowpass hidden" type="checkbox" name="showpasses" id="showpasses"/>
				<div class="input-holder">
					<label for="registername"><i class="fas fa-user"></i></label>
					<input id="registername" type="text" placeholder="Login" name="reguser" required/>
				</div>
				<div class="input-holder">
					<label for="registerpassword"><i class="fas fa-lock"></i></label>
					<input id="registerpassword" type="password" placeholder="Password" name="regpassword"/>
				</div>
				<div class="input-holder">
					<label for="registerconfirm"><i class="fas fa-check-double"></i></label>
					<input id="registerconfirm" type="password" placeholder="Confirm password" name="confirmpassword"/>
				</div>
				<label class="showpass" for="showpasses">Показать пароли&nbsp;<i class="fas fa-eye"></i></label>
			</fieldset>
			<label class="lable active" for="checkreg" title="Вернуться к вводу учетных данных">Уже зарегистрирован?</label>
			<div class="flex-column tail">
				<button type="submit" class="btn on-the-glass">Зарегистрироваться</button>
			</div>
			<div class="auth-bg-blink"></div>
		</form>
		<!-- <form class="recoverform" action="post" name="signup" id="register">

		</form> -->
		<!-- <div class="logoround"></div> -->
	</div>
</div>
