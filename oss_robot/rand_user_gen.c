#include <stdio.h>
#include <string.h>
#include <math.h>
#include <stdlib.h>

int main(int ac, char** av)
{
	int i, j;
	srand(time(NULL));

	for( i = 0; i < 10; i++ )
	{
		for( j = 0; j < 8; j++ )
		{
			//printf("%c", rand() % 26 + 65  );
			printf("%c", rand() % 26 + 97  );
		}
		putchar('\n');
	}

	putchar('\n');

	return 0;	
}

