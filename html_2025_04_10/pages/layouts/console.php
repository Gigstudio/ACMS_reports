<div class="console">
    <div class="win-control">
        <div class="winfilter-holder" id="terminal_filters">
            <input class="horizontal-radio" type="radio" name="log_filter" value="0" title="Все" checked/>
            <input class="horizontal-radio" type="radio" name="log_filter" value="2" title="Предупреждения"/>
            <input class="horizontal-radio" type="radio" name="log_filter" value="3" title="Только ошибки"/>
        </div>
        <div class="winc-btn actionbtn" id="clearbtn" title="Очистить" style="margin-left: 20px;">
                <i class="fas fa-broom"></i>
            </div>
        <div class="winc-holder" id="terminal_controls">
            <div class="winc-btn" title="Свернуть">
                <i class="fas fa-window-minimize"></i>
            </div>
            <div class="winc-btn" title="Закрыть">
                <i class="fas fa-times"></i>
            </div>
        </div>
    </div>
    <div class="resizer"></div>
    <div class="terminal" id="console"></div>
</div>
