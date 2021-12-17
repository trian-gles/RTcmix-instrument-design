#include <Instrument.h>		  // the base class for this instrument

class TEMPLATE : public Instrument {

public:
	TEMPLATE();
	virtual ~TEMPLATE();
	virtual int init(double *, int);
	virtual int configure();
	virtual int run();

private:
};
