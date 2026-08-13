import inspect
from pymeeus.Earth import Earth
from pymeeus.Mercury import Mercury

with open('scratch/earth_source.py', 'w') as f:
    f.write(inspect.getsource(Earth))

with open('scratch/mercury_source.py', 'w') as f:
    f.write(inspect.getsource(Mercury))

print("Sources written")
