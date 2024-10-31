<div class="wrap">
	<p>
		<h2>Missing function</h2>
		<p>Function <b><?echo $this->function_name; ?></b> not found</p>
		<p>Create it on <b><?echo $this->controller_file; ?></b></p>
	</p>

	<p>
		<h2>Example</h2>
		<p>
			&nbsp;&nbsp;Class <?echo $this->controller_name; ?> { <br/>
				&nbsp;&nbsp;&nbsp;&nbsp; function <?echo $this->function_name; ?> () { <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;} <br/>
			&nbsp;&nbsp;}
		</p>
	</p>

</div>
