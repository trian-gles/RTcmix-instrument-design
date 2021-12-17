#include <stdio.h>
#include <stdlib.h>
#include <ugens.h>
#include "TEMPLATE.h"
#include <rt.h>
#include <rtdefs.h>


TEMPLATE::TEMPLATE(
{
}


TEMPLATE::~TEMPLATE()
{
}



int TEMPLATE::init(double p[], int n_args)
{
}



int TEMPLATE::configure()
{
	return 0;
}

int TEMPLATE::run()
{
	for (int i = 0; i < framesToRun(); i++) {
		increment();
	}

	return framesToRun();
}

Instrument *makeTEMPLATE()
{
	TEMPLATE *inst = new TEMPLATE();
	inst->set_bus_config("TEMPLATE");

	return inst;
}

void rtprofile()
{
	RT_INTRO("TEMPLATE", makeTEMPLATE);
}


