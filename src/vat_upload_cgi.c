#include <stdio.h>
#include <stdlib.h>
#include <bios/html.h>


int main(int argc, char **argv)
{
    cgiInit ();
    cgiHeader ("text/html");

    printf ("Upload page\n");
    fflush (stdout);


    return 0;
}
