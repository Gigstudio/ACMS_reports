<div class="login-wrapper" id="modalbg">
	<input class="hidden" type="checkbox" id="checkreg" />

	<div class="closemodal" id="close_login"><i class="fas fa-times"></i></div>

	<div class="auth-bg" id="forms_holder">
		<!-- Вход -->
		<form class="authform" method="post" name="signin" id="login">
			<div class="reg-step fullheight">
				<fieldset class="inputset" id="logininputs">
					<input class="cehcktoshowpass hidden" type="checkbox" id="showpass"/>
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
					<label class="label" for="remember">Запомнить</label>
					<label class="label active right" for="checkreg">Нет учетной записи?</label>
				</div>
				<div class="flex-column tail">
					<button type="submit" class="btn on-the-glass">Войти</button>
				</div>
				<div class="auth-bg-blink"></div>
			</div>
		</form>

		<!-- Регистрация -->
		<form class="authform" method="post" name="signup" id="register">
			<div class="reg-slide-window">
				<div class="reg-wrapper">
					<!-- Этап 1: поиск по пропуску -->
					<div class="reg-step reg-step1">
						<fieldset class="inputset">
							<div class="input-holder">
								<label for="badge"><i class="fas fa-id-card"></i></label>
								<input id="badge" type="text" placeholder="Номер пропуска" name="badge" required pattern="\d+" title="Допустимы только цифры"/>
							</div>
						</fieldset>
						<div class="lineholder fullwidth">
							<label class="label active" for="checkreg" title="Вернуться к вводу учетных данных">Уже зарегистрирован?</label>
						</div>
						<div class="inputset hint">
							<div class="hint-text">
								<br>
								<i class="fas fa-info-circle"></i> Номер пропуска находится на его обратной стороне, у нижнего края карточки
							</div>
							<div class="card-wrapper">
								<div class="card">
									<div class="card-front">
										<div class="company">«Организация»</div>
										<div class="bage-credentials">
											<div class="photo-example"><i class="fas fa-user"></i></div>
											<div class="cred-example">
												Фамилия<br>
												Имя<br>
												Отчество
											</div>
										</div>
									</div>
									<div class="card-back">
										<span class="constant-blink">XXXXXXXXXX</span><span>XXX.XXXXX</span> 
									</div>
								</div>
							</div>
						</div>
						<div class="flex-column tail">
							<button type="button" id="reg-next" class="btn on-the-glass disabled" disabled title="Проверить и продолжить">Найти</button>
						</div>
					</div>

					<!-- Этап 2: регистрация -->
					<div class="reg-step reg-step2">
						<fieldset class="inputset">
							<div class="input-holder">
								<label for="reguser"><i class="fas fa-user"></i></label>
								<input id="reguser" type="text" placeholder="Придумайте логин" name="reguser" required/>
							</div>
							<div class="input-holder">
								<label for="regpassword"><i class="fas fa-lock"></i></label>
								<input id="regpassword" type="password" placeholder="Пароль" name="regpassword" required/>
							</div>
							<div class="input-holder">
								<label for="confirmpassword"><i class="fas fa-check-double"></i></label>
								<input id="confirmpassword" type="password" placeholder="Повторите пароль" name="confirmpassword" required/>
							</div>
						</fieldset>
						<div class="lineholder fullwidth">
							<span class="label active" id="reg-prev">← Назад</span>
							<label class="label active right" for="checkreg" title="Вернуться к вводу учетных данных">Уже зарегистрирован?</label>
						</div>
						<div class="flex-column tail">
							<button type="submit" class="btn on-the-glass disabled">Зарегистрироваться</button>
						</div>
					</div>
				</div>
			</div>
		</form>	
	</div>
</div>
