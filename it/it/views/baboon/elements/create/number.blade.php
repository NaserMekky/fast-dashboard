<?php

if ($data['use_collective'] == 'yes') {
	$number = '
<div class="' . $data['col_width'] . '">
    <div class="form-group">
        {!! Form::label(\'{Convention}\',trans(\'{lang}.{Convention}\'),[\'class\'=>\' control-label\']) !!}
            {!! Form::number(\'{Convention}\',old(\'{Convention}\'),[\'class\'=>\'form-control\',\'placeholder\'=>trans(\'{lang}.{Convention}\')]) !!}
    </div>
</div>
';
} else {
	$number = '
<div class="' . $data['col_width'] . '">
    <div class="form-group">
        <label for="{Convention}" class=" control-label">{{trans(\'{lang}.{Convention}\')}}</label>
            <input type="number" id="{Convention}" name="{Convention}" value="{{old(\'{Convention}\')}}" class="form-control" placeholder="{{trans(\'{lang}.{Convention}\')}}" />
    </div>
</div>
';
}
$number = str_replace('{Convention}', $data['col_name_convention'], $number);
$number = str_replace('{lang}', $data['lang_file'], $number);
echo $number;
?>